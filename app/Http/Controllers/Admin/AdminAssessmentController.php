<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Assessment;
use App\Models\DragDropItem;
use App\Models\ImageIdentificationItem;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAssessmentController extends Controller
{
    /**
     * Record an admin activity.
     */
    private function logActivity(string $action, string $description): void
    {
        $admin = auth('sanctum')->user();

        if (! $admin) {
            return;
        }

        AdminLog::create([
            'admin_id' => $admin->id,
            'action_type' => $action,
            'description' => $description,
        ]);
    }

    /**
     * Update a multiple-choice quiz and its questions.
     *
     * Image upload field:
     * questions[index][image]
     *
     * To remove an existing image, send:
     * questions[index][remove_image] = 1
     */
    public function updateMultipleChoice(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:1000'],
            'questions.*.correct_option' => [
                'required',
                'integer',
                'between:0,3',
            ],
            'questions.*.image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120',
            ],
            'questions.*.remove_image' => ['nullable', 'boolean'],
        ]);

        try {
            $quiz = DB::transaction(function () use ($validated, $request, $id) {
                $quiz = Quiz::query()->findOrFail($id);

                $quiz->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'difficulty' => $validated['difficulty'] ?? null,
                ]);

                $submittedQuestionIds = [];

                foreach ($validated['questions'] as $index => $questionData) {
                    if (! empty($questionData['id'])) {
                        $question = Question::query()
                            ->where('quiz_id', $quiz->id)
                            ->find($questionData['id']);

                        if (! $question) {
                            throw ValidationException::withMessages([
                                "questions.$index.id" => 'The selected question does not belong to this quiz.',
                            ]);
                        }
                    } else {
                        $question = new Question;
                        $question->quiz_id = $quiz->id;
                    }

                    $question->question_text = $questionData['text'];

                    if ($request->hasFile("questions.$index.image")) {
                        $this->deleteStoredImage($question->image_path);

                        $question->image_path = $request
                            ->file("questions.$index.image")
                            ->store('questions', 'public');
                    } elseif (! empty($questionData['remove_image'])) {
                        $this->deleteStoredImage($question->image_path);
                        $question->image_path = null;
                    }

                    $question->save();
                    $submittedQuestionIds[] = $question->id;

                    $this->syncQuestionOptions(
                        $question,
                        $questionData['options'],
                        (int) $questionData['correct_option']
                    );
                }

                $removedQuestions = Question::query()
                    ->where('quiz_id', $quiz->id)
                    ->whereNotIn('id', $submittedQuestionIds)
                    ->get();

                foreach ($removedQuestions as $removedQuestion) {
                    $this->deleteStoredImage($removedQuestion->image_path);
                    $removedQuestion->delete();
                }

                $this->logActivity(
                    'Update',
                    "Competency '{$quiz->title}' (ID: {$quiz->id}) was updated."
                );

                return $quiz;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz updated successfully.',
                'quiz_id' => $quiz->id,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the quiz.',
            ], 500);
        }
    }

    /**
     * Synchronize question options and answer records.
     */
    private function syncQuestionOptions(Question $question, array $optionTexts, int $correctIndex): void
    {
        $existingOptions = DB::table('question_options')
            ->where('question_id', $question->id)
            ->orderBy('id')
            ->get();

        DB::table('answers')
            ->where('question_id', $question->id)
            ->delete();

        $retainedOptionIds = [];

        foreach ($optionTexts as $index => $optionText) {
            $isCorrect = $index === $correctIndex;

            if (isset($existingOptions[$index])) {
                $optionId = $existingOptions[$index]->id;

                DB::table('question_options')
                    ->where('id', $optionId)
                    ->update([
                        'option_text' => $optionText,
                        'is_correct' => $isCorrect,
                    ]);
            } else {
                $optionId = DB::table('question_options')->insertGetId([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                ]);
            }

            $retainedOptionIds[] = $optionId;

            DB::table('answers')->insert([
                'question_id' => $question->id,
                'answer_text' => $optionText,
                'is_correct' => $isCorrect,
            ]);
        }

        $optionsToDelete = DB::table('question_options')
            ->where('question_id', $question->id);

        if ($retainedOptionIds !== []) {
            $optionsToDelete->whereNotIn('id', $retainedOptionIds);
        }

        $optionsToDelete->delete();
    }

    /**
     * Delete an image from the public storage disk.
     */
    private function deleteStoredImage(?string $path): void
    {
        if (
            $path !== null &&
            ! filter_var($path, FILTER_VALIDATE_URL) &&
            Storage::disk('public')->exists($path)
        ) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Delete a quiz.
     */
    /**
     * Delete a COC and everything under it.
     * Blocked when any learner has a completed result, so records are never lost.
     */
    public function deleteQuiz(int $id): JsonResponse
    {
        $quiz = Quiz::query()->findOrFail($id);

        $hasResults = QuizRecord::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->exists();

        if ($hasResults) {
            return response()->json([
                'status' => 'error',
                'message' => 'This COC has learner results, so it cannot be deleted. Hide its assessments instead.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($quiz) {
                $title = $quiz->title;
                $quizId = $quiz->id;

                // Unfinished attempts (no completed_at) can be removed with the COC.
                $recordIds = QuizRecord::query()
                    ->where('quiz_id', $quizId)
                    ->pluck('id');

                DB::table('quiz_attempts')->whereIn('quiz_record_id', $recordIds)->delete();
                QuizRecord::query()->whereIn('id', $recordIds)->delete();

                // Questions: options, answers, drag-drop items, and image files.
                $questions = Question::query()->where('quiz_id', $quizId)->get();
                $questionIds = $questions->pluck('id');

                DB::table('answers')->whereIn('question_id', $questionIds)->delete();
                DB::table('question_options')->whereIn('question_id', $questionIds)->delete();
                DragDropItem::query()->whereIn('question_id', $questionIds)->delete();

                foreach ($questions as $question) {
                    $this->deleteStoredImage($question->image_path);
                }

                Question::query()->whereIn('id', $questionIds)->delete();

                // Assessments: image identification items and their files first.
                $assessments = Assessment::query()->where('coc_id', $quizId)->get();

                foreach ($assessments as $assessment) {
                    if ($assessment->type === 'image_identification') {
                        ImageIdentificationItem::query()
                            ->where('assessment_id', $assessment->id)
                            ->get()
                            ->each(function (ImageIdentificationItem $item) {
                                $this->deleteStoredImage($item->image_path);
                                $item->delete();
                            });
                    }
                    $assessment->delete();
                }
                $quiz->delete();

                $this->logActivity(
                    'Delete',
                    "COC '{$title}' (ID: {$quizId}) and its assessments were deleted."
                );
            });

            return response()->json([
                'status' => 'success',
                'message' => 'COC deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete the COC.',
            ], 500);
        }
    }

    /**
     * Return all quizzes.
     */
    public function allQuizzes()
    {
        return response()->json([
            'status' => 'success',
            'result' => Quiz::query()->latest()->get(),
        ]);
    }

    private function cocPayload(Quiz $coc): array
    {
        return [
            'id' => (int) $coc->id,
            'title' => $coc->title,
            'description' => $coc->description,
            'quiz_type' => $coc->quiz_type,
            'coc_number' => $coc->coc_number !== null ? (int) $coc->coc_number : null,
            'difficulty' => $coc->difficulty,
            'category' => $coc->category,
        ];
    }

    /**
     * Return single coc.
     */
    public function showCoc(int $id): JsonResponse
    {
        $coc = Quiz::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->cocPayload($coc),
        ]);
    }

    /**
     * Update single coc
     */
    public function updateCoc(Request $request, int $id): JsonResponse
    {
        $coc = Quiz::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quiz_type' => ['required', 'string', 'max:50'],
            'coc_number' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                Rule::unique('quizzes', 'coc_number')->ignore($coc->id),
            ],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'category' => ['required', 'string', 'max:150'],
        ]);

        $coc->update($validated);
        $this->logActivity('Update', "COC #{$coc->id} updated successfully");

        return response()->json([
            'success' => true,
            'message' => 'COC updated successfully.',
            'data' => $this->cocPayload($coc->fresh()),
        ]);
    }

    /**
     * Return assessments for a COC.
     */
    public function allAssessment(int $id)
    {
        return response()->json([
            'status' => 'success',
            'result' => Assessment::query()
                ->where('coc_id', $id)
                ->get(),
        ]);
    }

    /**
     * Return a quiz and its multiple-choice questions for editing.
     */
    public function editMultipleChoice(int $id)
    {
        $quiz = Quiz::query()->find($id);

        if (! $quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found.',
            ], 404);
        }

        $questions = Question::query()
            ->where('quiz_id', $quiz->id)
            ->where(function ($query) {
                $query->whereNull('question_type')
                    ->orWhere('question_type', '!=', 'drag_drop');
            })
            ->with([
                'options' => function ($query) {
                    $query->select(
                        'id',
                        'question_id',
                        'option_text',
                        'is_correct'
                    );
                },
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Question $question) {
                $question->image_url = $question->image_path
                    ? Storage::disk('public')->url($question->image_path)
                    : null;

                $question->correct_option_id = $question->options
                    ->firstWhere('is_correct', true)?->id;

                return $question;
            });

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    /**
     * Delete one assessment. Blocked when learners already have results,
     * because those records would point at a type that no longer exists.
     */
    public function deleteAssessment(int $id): JsonResponse
    {
        $assessment = Assessment::query()->findOrFail($id);

        $hasResults = QuizRecord::query()
            ->where('quiz_id', $assessment->coc_id)
            ->where('quiz_type', $assessment->type)
            ->whereNotNull('completed_at')
            ->exists();

        if ($hasResults) {
            return response()->json([
                'status' => 'error',
                'message' => 'This assessment already has learner results, so it cannot be deleted. Hide it instead.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($assessment) {
                // Image identification items belong to the assessment, so remove them with their files.
                if ($assessment->type === 'image_identification') {
                    ImageIdentificationItem::query()
                        ->where('assessment_id', $assessment->id)
                        ->get()
                        ->each(function (ImageIdentificationItem $item) {
                            $this->deleteStoredImage($item->image_path);
                            $item->delete();
                        });
                }

                $title = $assessment->title;
                $id = $assessment->id;

                $assessment->delete();

                $this->logActivity(
                    'Delete',
                    "Assessment '{$title}' (ID: {$id}) was deleted."
                );
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Assessment deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete the assessment.',
            ], 500);
        }
    }

    /**
     * Create a multiple-choice quiz and its questions.
     */
    public function createQuiz(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:1000'],
            'questions.*.correct_option' => [
                'required',
                'integer',
                'between:0,3',
            ],
            'questions.*.image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120',
            ],
        ]);

        try {
            $quiz = DB::transaction(function () use ($validated, $request) {
                $quiz = Quiz::query()->create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'difficulty' => $validated['difficulty'] ?? null,
                ]);

                foreach ($validated['questions'] as $index => $questionData) {
                    $question = new Question([
                        'question_text' => $questionData['text'],
                    ]);

                    $question->quiz_id = $quiz->id;

                    if ($request->hasFile("questions.$index.image")) {
                        $question->image_path = $request
                            ->file("questions.$index.image")
                            ->store('questions', 'public');
                    }

                    $question->save();

                    $this->syncQuestionOptions(
                        $question,
                        $questionData['options'],
                        (int) $questionData['correct_option']
                    );
                }

                $this->logActivity(
                    'New Competency',
                    "Competency '{$quiz->title}' (ID: {$quiz->id}) was created."
                );

                return $quiz;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz created successfully.',
                'quiz_id' => $quiz->id,
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to create the quiz.',
            ], 500);
        }
    }
}
