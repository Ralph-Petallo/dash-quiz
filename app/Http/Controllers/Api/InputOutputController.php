<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\InputOutput;
use App\Models\InputOutputAttempt;
use App\Models\QuizRecord;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InputOutputController extends Controller
{
    private const PASSING_SCORE = 75.00;

    private const ASSESSMENT_TYPE = 'input_output';

    private const POINTS_PER_CORRECT = 10;

    /**
     * Return the active device bank.a
     * Never expose the correct category here.
     */
    public function index(): JsonResponse
    {
        $assessment = Assessment::query()
            ->where('type', self::ASSESSMENT_TYPE)
            ->where('available', true)
            ->first();

        if (! $assessment) {
            return response()->json([
                'success' => false,
                'message' => 'No available input/output assessment was found.',
            ], 404);
        }

        $devices = InputOutput::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->get(['id', 'name', 'image_key', 'description']);

        if ($devices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No input/output devices are available yet.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'assessment_id' => (int) $assessment->id,
            'data' => $devices,
        ]);
    }

    /**
     * Validate one classification and calculate the score.
     */
    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => ['required', 'integer', 'exists:assessments,id'],
            'session_uuid' => ['required', 'uuid'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*.device_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('input_output_devices', 'id')->where('is_active', true),
            ],
            'placements.*.selected_type' => ['required', Rule::in(['input', 'output'])],
        ]);

        $user = $request->user();

        $assessment = Assessment::query()
            ->whereKey($validated['assessment_id'])
            ->where('type', self::ASSESSMENT_TYPE)
            ->where('available', true)
            ->firstOrFail();

        $devices = InputOutput::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $expectedIds = $devices->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
        $submittedIds = collect($validated['placements'])
            ->pluck('device_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($expectedIds !== $submittedIds) {
            return response()->json([
                'success' => false,
                'message' => 'Please classify every device before submitting.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($user, $assessment, $validated, $devices) {
                $sessionUuid = $validated['session_uuid'];

                $alreadySubmitted = InputOutputAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('session_uuid', $sessionUuid)
                    ->exists();

                if ($alreadySubmitted) {
                    throw new ConflictHttpException('This assessment has already been submitted.');
                }

                $review = [];
                $correct = 0;

                foreach ($validated['placements'] as $placement) {
                    $device = $devices->get((int) $placement['device_id']);
                    $isCorrect = $device->type === $placement['selected_type'];

                    $correct += $isCorrect ? 1 : 0;

                    $review[] = [
                        'device_id' => $device->id,
                        'name' => $device->name,
                        'selected_type' => $placement['selected_type'],
                    ];
                }

                $total = $devices->count();

                $attemptNumber = QuizRecord::attemptsFor(
                    $user->id,
                    $assessment->coc_id,
                    $assessment->type
                )->lockForUpdate()->count() + 1;

                $percentage = round(($correct / max(1, $total)) * 100, 2);
                $passingScore = self::PASSING_SCORE;
                $status = $percentage >= $passingScore ? 'Passed' : 'Failed';

                $record = QuizRecord::create([
                    'user_id' => $user->id,
                    'quiz_id' => $assessment->coc_id,
                    'quiz_type' => $assessment->type,
                    'category' => $assessment->category ?? 'Hardware',
                    'score' => $correct,
                    'correct_answers' => $correct,
                    'total_questions' => $total,
                    'percentage' => $percentage,
                    'passing_score' => $passingScore,
                    'status' => $status,
                    'attempt_count' => $attemptNumber,
                    'elapsed_time' => isset($validated['duration_ms'])
                        ? (int) round($validated['duration_ms'] / 1000)
                        : null,
                    'completed_at' => now(),
                ]);

                foreach ($validated['placements'] as $placement) {
                    $device = $devices->get((int) $placement['device_id']);
                    $isCorrect = $device->type === $placement['selected_type'];

                    InputOutputAttempt::create([
                        'user_id' => $user->id,
                        'quiz_record_id' => $record->id,
                        'session_uuid' => $sessionUuid,
                        'device_id' => $device->id,
                        'selected_type' => $placement['selected_type'],
                        'is_correct' => $isCorrect,
                        'points' => $isCorrect ? self::POINTS_PER_CORRECT : 0,
                    ]);
                }

                return [
                    'record_id' => (int) $record->id,
                    'attempt_count' => (int) $attemptNumber,
                    'score' => $correct,
                    'correct' => $correct,
                    'total' => $total,
                    'accuracy' => $percentage,
                    'status' => $status,
                    'passed' => $status === 'Passed',
                    'complete' => true,
                    'review' => $review,
                ];
            });
        } catch (QueryException $e) {
            // A parallel request got past the check; the unique index rejected this one.
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'This assessment has already been submitted.',
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Retrieve progress for the current activity session.
     */
    public function result(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_uuid' => ['required', 'uuid'],
        ]);

        $userId = $request->user()->getAuthIdentifier();

        // Get all attempts for the user and session, including the associated QuizRecord if it exists
        $attempts = InputOutputAttempt::query()
            ->where('user_id', $userId)
            ->where('session_uuid', $validated['session_uuid'])
            ->with('record:id,score,correct_answers,total_questions,percentage,status')
            ->get();

        $record = $attempts->first()?->record; // Get the associated QuizRecord if it exists

        $total = $record?->total_questions ?? InputOutput::where('is_active', true)->count();
        $placed = $record?->correct_answers ?? 0;
        $attemptCount = $attempts->count();

        return response()->json([
            'success' => true,
            'data' => [
                'record_id' => $record?->id,
                'score' => $record?->score ?? 0,
                'placed' => $placed,
                'total' => $total,
                'attempts' => $attemptCount,
                'accuracy' => $record
                    ? (float) $record->percentage
                    : ($attemptCount > 0 ? (int) round(($placed / $attemptCount) * 100) : 0),
                'status' => $record?->status,
                'complete' => $record !== null,
            ],
        ]);
    }

    // ------------------------ADMIN BELOW-------------//
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->role === 'admin',
            403,
            'Not authorized.'
        );
    }

    public function edit(Request $request, int|string $assessmentId): JsonResponse
    {
        $this->authorizeAdmin($request);

        $assessment = Assessment::query()
            ->whereKey($assessmentId)
            ->where('type', self::ASSESSMENT_TYPE)
            ->firstOrFail();

        $devices = InputOutput::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'type',
                'image_key',
                'description',
                'is_active',
                'sort_order',
            ]);

        return response()->json([
            'success' => true,
            'assessment_id' => (int) $assessment->id,
            'assessment' => $assessment,
            'data' => $devices,
        ]);
    }

    public function update(Request $request, int $assessmentId): JsonResponse
    {
        $this->authorizeAdmin($request);

        Assessment::query()
            ->whereKey($assessmentId)
            ->where('type', self::ASSESSMENT_TYPE)
            ->firstOrFail();

        $validated = $request->validate([
            'devices' => ['required', 'array'],
            'devices.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('input_output_devices', 'id'),
            ],
            'devices.*.name' => ['required', 'string', 'max:100'],
            'devices.*.type' => ['required', Rule::in(['input', 'output'])],
            'devices.*.image_key' => ['nullable', 'string', 'max:255'],
            'devices.*.description' => ['nullable', 'string', 'max:255'],
            'devices.*.is_active' => ['required', 'boolean'],
            'devices.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $savedDevices = DB::transaction(function () use ($validated) {
            $submittedIds = collect($validated['devices'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            foreach ($validated['devices'] as $deviceData) {
                $attributes = [
                    'name' => trim($deviceData['name']),
                    'type' => $deviceData['type'],
                    'image_key' => $deviceData['image_key'] ?? null,
                    'description' => $deviceData['description'] ?? null,
                    'is_active' => $deviceData['is_active'],
                    'sort_order' => $deviceData['sort_order'],
                ];

                if (! empty($deviceData['id'])) {
                    InputOutput::query()
                        ->whereKey($deviceData['id'])
                        ->update($attributes);
                } else {
                    InputOutput::create($attributes);
                }
            }

            // Deactivate omitted devices rather than deleting records that may be
            // referenced by existing attempts.
            InputOutput::query()
                ->when(
                    $submittedIds->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('id', $submittedIds),
                    fn ($query) => $query
                )
                ->update(['is_active' => false]);

            return InputOutput::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get([
                    'id',
                    'name',
                    'type',
                    'image_key',
                    'description',
                    'is_active',
                    'sort_order',
                ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Device bank updated successfully.',
            'data' => $savedDevices,
        ]);
    }
}
