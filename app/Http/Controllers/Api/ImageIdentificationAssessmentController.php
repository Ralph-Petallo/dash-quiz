<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ImageIdentificationAttempt;
use App\Models\ImageIdentificationItem;
use App\Models\Quiz;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImageIdentificationAssessmentController extends Controller
{
    private const ASSESSMENT_TYPE = 'image_identification';

    private const PASSING_SCORE = 75.00;

    private const QUESTION_LIMIT = 10;

    private const DIFFICULTIES = ['Easy', 'Medium', 'Hard'];

    /**
     * GET /api/image-identification/{quizId}
     *
     * quizId is the COC/Quiz ID. Resolve its selected assessment,
     * then load image items by assessment_id.
     */
    public function show(Request $request, int $quizId): JsonResponse
    {
        $assessment = Assessment::query()
            ->where('coc_id', $quizId)
            ->where('type', self::ASSESSMENT_TYPE)
            ->where('available', true)
            ->first();

        if (!$assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No available image identification assessment was found.',
            ], 404);
        }

        $items = ImageIdentificationItem::query()
            ->where('assessment_id', $assessment->id)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->whereNotNull('image_path')
            ->inRandomOrder()
            ->take(self::QUESTION_LIMIT)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No image identification items found for this assessment.',
            ], 404);
        }

        $questionSet = [];
        $questions = $items->map(function (ImageIdentificationItem $item) use (&$questionSet) {
            $questionToken = (string) Str::uuid();
            $optionMap = [];
            $options = $this->generateOptions($item)
                ->map(function (array $option) use (&$optionMap) {
                    $optionToken = (string) Str::uuid();
                    $optionMap[$optionToken] = (int) $option['id'];

                    return [
                        'id' => $optionToken,
                        'description' => $option['description'],
                    ];
                })
                ->values();

            $questionSet[$questionToken] = [
                'item_id' => (int) $item->id,
                'options' => $optionMap,
            ];

            return [
                'id' => $questionToken,
                'question_text' => 'Identify the use of the image shown.',
                'image' => [
                    'image_url' => $this->imageUrl($item->image_path),
                ],
                'options' => $options,
            ];
        })->values();

        $attempt = ImageIdentificationAttempt::query()->create([
            'user_id' => $request->user()->id,
            'assessment_id' => $assessment->id,
            'quiz_id' => $quizId,
            'token' => (string) Str::uuid(),
            'question_set' => $questionSet,
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'status' => 'success',
            'quiz_id' => $quizId,
            'assessment_id' => $assessment->id,
            'assessment_type' => $assessment->type,
            'attempt_token' => $attempt->token,
            'questions' => $questions,
        ]);
    }

    /**
     * POST /api/image-identification/answer
     *
     * The issued token binds answers to one server-selected question set.
     */
    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'attempt_token' => ['required', 'uuid'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'uuid', 'distinct'],
            'answers.*.answer_id' => ['required', 'uuid'],
            'elapsed_time' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $attempt = ImageIdentificationAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['attempt_token'])
            ->first();

        if (!$attempt || (int) $attempt->quiz_id !== (int) $validated['quiz_id']) {
            return $this->error('The assessment attempt is invalid or has expired.', 404);
        }

        $result = DB::transaction(function () use ($request, $validated, $attempt) {
            $attempt = ImageIdentificationAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->submitted_at !== null) {
                abort(409, 'This assessment attempt has already been submitted.');
            }

            if ($attempt->expires_at->isPast()) {
                abort(410, 'This assessment attempt has expired. Start a new attempt.');
            }

            $assessment = Assessment::query()
                ->whereKey($attempt->assessment_id)
                ->where('coc_id', $attempt->quiz_id)
                ->where('type', self::ASSESSMENT_TYPE)
                ->where('available', true)
                ->first();

            if (!$assessment) {
                abort(404, 'No available image identification assessment was found.');
            }

            $questionSet = $attempt->question_set;
            $answers = collect($validated['answers'])->keyBy('question_id');
            $expectedQuestionIds = array_keys($questionSet);

            if (
                $answers->count() !== count($expectedQuestionIds) ||
                $answers->keys()->sort()->values()->all() !== collect($expectedQuestionIds)->sort()->values()->all()
            ) {
                throw ValidationException::withMessages([
                    'answers' => ['Submit exactly one answer for every issued question.'],
                ]);
            }

            $itemIds = collect($questionSet)
                ->flatMap(fn(array $question) => array_merge(
                    [(int) $question['item_id']],
                    array_map('intval', array_values($question['options']))
                ))
                ->unique()
                ->values();

            $items = ImageIdentificationItem::query()
                ->where('assessment_id', $assessment->id)
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                abort(409, 'The image identification questions have changed. Start a new attempt.');
            }

            $correctCount = 0;

            foreach ($questionSet as $questionToken => $questionData) {
                $answer = $answers->get($questionToken);
                $optionMap = $questionData['options'];

                if (!array_key_exists($answer['answer_id'], $optionMap)) {
                    throw ValidationException::withMessages([
                        'answers' => ['A selected option does not belong to its issued question.'],
                    ]);
                }

                $question = $items->get((int) $questionData['item_id']);
                $selected = $items->get((int) $optionMap[$answer['answer_id']]);

                if ($this->descriptionsMatch($selected->description, $question->description)) {
                    $correctCount++;
                }
            }

            $total = count($questionSet);
            $percentage = round($correctCount / $total * 100, 2);
            $resultStatus = $percentage >= self::PASSING_SCORE ? 'Passed' : 'Failed';
            $elapsedTime = (int) ($validated['elapsed_time'] ?? 0);
            $previousAttempts = QuizRecord::attemptsFor(
                $request->user()->id,
                (int) $attempt->quiz_id,
                self::ASSESSMENT_TYPE
            )->lockForUpdate()->count();

            $record = QuizRecord::create([
                'user_id' => $request->user()->id,
                'quiz_id' => $attempt->quiz_id,
                'score' => $correctCount,
                'correct_answers' => $correctCount,
                'total_questions' => $total,
                'percentage' => $percentage,
                'passing_score' => self::PASSING_SCORE,
                'status' => $resultStatus,
                'attempt_count' => $previousAttempts + 1,
                'quiz_type' => self::ASSESSMENT_TYPE,
                'category' => $assessment->category ?? 'Hardware',
                'elapsed_time' => $elapsedTime,
                'completed_at' => now(),
            ]);

            $attempt->submitted_at = now();
            $attempt->save();

            return [
                'record' => $record,
                'correct' => $correctCount,
                'total' => $total,
                'percentage' => $percentage,
                'status' => $resultStatus,
                'elapsed_time' => $elapsedTime,
            ];
        });

        return response()->json([
            'status' => 'success',
            'record_id' => $result['record']->id,
            'score' => $result['correct'],
            'correct' => $result['correct'],
            'incorrect' => $result['total'] - $result['correct'],
            'total' => $result['total'],
            'percentage' => $result['percentage'],
            'passing_score' => self::PASSING_SCORE,
            'result_status' => $result['status'],
            'elapsed_time' => $result['elapsed_time'],
        ]);
    }

    /**
     * GET /api/admin/assessment/image-identification/edit/{assessmentId}
     *
     * Returns the full assessment record and its image items.
     */
    public function edit(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $assessment = Assessment::query()
            ->where('type', self::ASSESSMENT_TYPE)
            ->findOrFail($assessmentId);

        $items = ImageIdentificationItem::query()
            ->where('assessment_id', $assessment->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn(ImageIdentificationItem $item) => [
                'id' => $item->id,
                'assessment_id' => $item->assessment_id,
                'item_name' => $item->item_name,
                'image_path' => $item->image_path,
                'image_url' => $this->imageUrl($item->image_path),
                'description' => $item->description,
                'sort_order' => $item->sort_order,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'assessment' => $this->assessmentPayload($assessment),
            'items' => $items,
        ]);
    }

    /**
     * PUT /api/admin/assessment/image-identification/update/{assessmentId}
     *
     * The Vue form sends POST with _method=PUT, so Laravel treats it as PUT.
     */
    public function update(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $validated = $request->validate([
            'assessment' => ['required', 'array'],
            'assessment.title' => ['required', 'string', 'max:255'],
            'assessment.description' => ['nullable', 'string', 'max:2000'],
            'assessment.category' => ['nullable', 'string', 'max:100'],
            'assessment.difficulty' => ['nullable', 'in:' . implode(',', self::DIFFICULTIES)],
            'assessment.icon' => ['nullable', 'string', 'max:100'],
            'assessment.available' => ['required', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['required', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'items.*.remove_image' => ['nullable', 'boolean'],
        ]);

        $assessment = Assessment::query()
            ->where('type', self::ASSESSMENT_TYPE)
            ->findOrFail($assessmentId);

        $assessmentData = $validated['assessment'];
        $submittedItems = $validated['items'];

        try {
            DB::transaction(function () use ($request, $assessment, $assessmentData, $submittedItems) {
                // Save the assessment record. coc_id, type, path and meta are not editable here.
                $assessment->title = $assessmentData['title'];
                $assessment->description = $assessmentData['description'] ?? null;
                $assessment->category = $assessmentData['category'] ?? null;
                $assessment->difficulty = $assessmentData['difficulty'] ?? null;
                $assessment->icon = $assessmentData['icon'] ?? null;
                $assessment->available = (bool) $assessmentData['available'];
                $assessment->save();

                $submittedItemIds = [];

                foreach ($submittedItems as $index => $itemData) {
                    if (!empty($itemData['id'])) {
                        $item = ImageIdentificationItem::query()
                            ->where('assessment_id', $assessment->id)
                            ->find($itemData['id']);

                        if (!$item) {
                            throw ValidationException::withMessages([
                                "items.$index.id" => 'This item does not belong to this assessment.',
                            ]);
                        }
                    } else {
                        $item = new ImageIdentificationItem;
                        $item->assessment_id = $assessment->id;
                    }

                    $item->item_name = $itemData['item_name'];
                    $item->description = $itemData['description'];
                    $item->sort_order = $itemData['sort_order'] ?? ($index + 1);

                    $image = $request->file("items.$index.image");

                    if ($image) {
                        $this->deleteStoredImage($item->image_path);
                        $item->image_path = $image->store('image-identification', 'public');
                    } elseif (!empty($itemData['remove_image'])) {
                        $this->deleteStoredImage($item->image_path);
                        $item->image_path = null;
                    }

                    if (!$item->image_path) {
                        throw ValidationException::withMessages([
                            "items.$index.image" => 'An image is required for each item.',
                        ]);
                    }

                    $item->save();
                    $submittedItemIds[] = $item->id;
                }

                // Remove items the admin deleted from the list.
                ImageIdentificationItem::query()
                    ->where('assessment_id', $assessment->id)
                    ->whereNotIn('id', $submittedItemIds)
                    ->get()
                    ->each(function (ImageIdentificationItem $item) {
                        $this->deleteStoredImage($item->image_path);
                        $item->delete();
                    });
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Image identification assessment updated successfully.',
                'assessment' => $this->assessmentPayload($assessment->fresh()),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the image identification assessment.',
            ], 500);
        }
    }

    /**
     * POST /api/admin/assessment/image-identification/create/{cocId}
     */
    public function store(Request $request, int $cocId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $quiz = Quiz::query()->findOrFail($cocId);

        $assessment = $this->findOrCreateAssessment($quiz);

        return response()->json([
            'status' => 'success',
            'assessment_id' => $assessment->id,
        ], $assessment->wasRecentlyCreated ? 201 : 200);
    }

    private function findOrCreateAssessment(Quiz $quiz): Assessment
    {
        return Assessment::query()->firstOrCreate(
            [
                'coc_id' => $quiz->id,
                'type' => self::ASSESSMENT_TYPE,
            ],
            [
                'title' => 'Image Identification',
                'description' => null,
                'category' => $quiz->category ?? 'Hardware',
                'difficulty' => $quiz->difficulty ?? 'Easy',
                'icon' => 'fa-solid fa-microchip',
                'available' => true,
                'path' => '/taking/image-identification',
            ]
        );
    }

    /**
     * Explicit shape for the assessment record, so the frontend always gets the same keys.
     */
    private function assessmentPayload(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'coc_id' => $assessment->coc_id,
            'type' => $assessment->type,
            'title' => $assessment->title,
            'description' => $assessment->description,
            'category' => $assessment->category,
            'difficulty' => $assessment->difficulty,
            'icon' => $assessment->icon,
            'available' => (bool) $assessment->available,
            'path' => $assessment->path,
            'meta' => $assessment->meta,
            'created_at' => $assessment->created_at,
            'updated_at' => $assessment->updated_at,
        ];
    }

    private function error(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['status' => 'error', 'message' => $message], $extra), $status);
    }

    private function descriptionsMatch(?string $selected, ?string $target): bool
    {
        if ($selected === null || $target === null) {
            return false;
        }

        return $this->normalizeDescription($selected) === $this->normalizeDescription($target);
    }

    private function normalizeDescription(string $text): string
    {
        // Collapse extra whitespace and ignore case so minor formatting differences don't matter.
        return mb_strtolower(Str::squish($text));
    }

    private function generateOptions(ImageIdentificationItem $correctItem)
    {
        $wrongOptions = ImageIdentificationItem::query()
            ->where('assessment_id', $correctItem->assessment_id)
            ->where('id', '!=', $correctItem->id)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->inRandomOrder()
            ->take(3)
            ->get(['id', 'description']);

        return $wrongOptions
            ->push((object) [
                'id' => $correctItem->id,
                'description' => $correctItem->description,
            ])
            ->shuffle()
            ->values()
            ->map(fn($option) => [
                'id' => $option->id,
                'description' => $option->description,
            ]);
    }

    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredImage(?string $path): void
    {
        if (
            $path &&
            !filter_var($path, FILTER_VALIDATE_URL) &&
            Storage::disk('public')->exists($path)
        ) {
            Storage::disk('public')->delete($path);
        }
    }
}
