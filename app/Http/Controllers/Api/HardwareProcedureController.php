<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\DragDropAttempt;
use App\Models\DragDropItem;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HardwareProcedureController extends Controller
{
    public function show(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');

        $question = $this->questionForQuiz($quiz);
        $items = $this->procedureItems($question->id);

        abort_if($items->isEmpty(), 404, 'No hardware practical procedure has been configured.');

        $assessment = Assessment::query()
            ->where('coc_id', $quiz->id)
            ->where('type', 'hardware_procedure')
            ->first();

        $components = $items
            ->groupBy('component_key')
            ->map(function ($rows) {
                /** @var DragDropItem $item */
                $item = $rows->first();

                return [
                    'component_key' => $item->component_key,
                    'display_name' => $item->display_name ?: $item->component_key,
                    'item_type' => $item->item_type ?: 'hardware',
                    'quantity' => (int) ($item->quantity ?: 1),
                    'image' => $item->item_image_path,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Hardware practical assessment loaded.',
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'quiz_type' => $quiz->quiz_type,
                    'coc_number' => $quiz->coc_number,
                    'difficulty' => $quiz->difficulty,
                    'category' => $quiz->category,
                ],
                'assessment' => $assessment ? [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'category' => $assessment->category,
                    'difficulty' => $assessment->difficulty,
                    'available' => (bool) $assessment->available,
                ] : null,
                'question' => [
                    'id' => $question->id,
                ],
                // Intentionally no correct order, instructions, action, or target zone.
                'components' => $components,
                'total_actions' => $items->count(),
                'passing_score' => 75,
            ],
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');

        $question = $this->questionForQuiz($quiz);
        $expected = $this->procedureItems($question->id)->values();
        abort_if($expected->isEmpty(), 422, 'No hardware practical procedure has been configured.');

        $attempt = DragDropAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('question_id', $question->id)
            ->whereNull('quiz_record_id')
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        if ($attempt && (int) $attempt->total_steps !== $expected->count()) {
            // Procedure changed while an unfinished development attempt existed.
            // Keep the old row for audit history and begin a clean attempt.
            $attempt = null;
        }

        if (!$attempt) {
            $attempt = DragDropAttempt::create([
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'quiz_record_id' => null,
                'user_id' => $user->id,
                'completed_steps' => 0,
                'correct_steps' => 0,
                'total_steps' => $expected->count(),
                'mistake_count' => 0,
                'percentage' => 0,
                'passed' => false,
                'elapsed_time' => null,
                'completed_at' => null,
            ]);
        }

        $attempt->load('steps');

        return response()->json([
            'success' => true,
            'message' => 'Practical attempt ready.',
            'data' => [
                'attempt_id' => $attempt->id,
                'phase' => $this->phaseForAttempt($attempt, $expected),
                'completed_actions' => (int) $attempt->completed_steps,
                'total_actions' => (int) $attempt->total_steps,
                'mistake_count' => (int) $attempt->mistake_count,
                'history' => $attempt->steps->map(fn($step) => [
                    'sequence_number' => (int) $step->sequence_number,
                    'performed_action' => $step->performed_action,
                    'component_key' => $step->performed_component_key,
                    'source_zone' => $step->source_zone,
                    'destination_zone' => $step->destination_zone,
                    'is_correct' => (bool) $step->is_correct,
                ])->values(),
            ],
        ]);
    }

    public function action(Request $request, Quiz $quiz, DragDropAttempt $attempt)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');
        $this->assertAttemptOwner($attempt, $quiz, $user->id);
        abort_if($attempt->completed_at || $attempt->quiz_record_id, 409, 'This attempt has already been submitted.');

        $validated = $request->validate([
            'component_key' => ['required', 'string', 'max:50'],
            'performed_action' => ['required', 'string', 'max:20'],
            'source_zone' => ['nullable', 'string', 'max:20'],
            'destination_zone' => ['required', 'string', 'max:20'],
        ]);

        $expected = $this->procedureItems($attempt->question_id)->values();
        $completed = (int) $attempt->steps()->where('is_correct', true)->count();
        $expectedItem = $expected->get($completed);

        if (!$expectedItem) {
            return response()->json([
                'success' => true,
                'message' => 'The practical procedure is already complete.',
                'data' => [
                    'allowed' => false,
                    'phase' => 'complete',
                    'completed_actions' => $completed,
                    'total_actions' => $expected->count(),
                    'mistake_count' => (int) $attempt->mistake_count,
                    'procedure_complete' => true,
                ],
            ]);
        }

        $knownComponent = $expected->contains(
            fn(DragDropItem $item) => $item->component_key === $validated['component_key']
        );
        abort_unless($knownComponent, 422, 'Unknown hardware component.');

        $isCorrect = $validated['component_key'] === $expectedItem->component_key
            && $validated['performed_action'] === $expectedItem->action
            && $validated['destination_zone'] === $expectedItem->target_zone;

        $hadPreviousAttemptForStep = $attempt->steps()
            ->where('expected_item_id', $expectedItem->id)
            ->exists();

        $firstTry = $isCorrect && !$hadPreviousAttemptForStep;
        $sequenceNumber = ((int) $attempt->steps()->max('sequence_number')) + 1;

        $attempt->steps()->create([
            'expected_item_id' => $expectedItem->id,
            'sequence_number' => $sequenceNumber,
            'expected_position' => (int) $expectedItem->correct_position,
            'phase' => $expectedItem->phase,
            'expected_action' => $expectedItem->action,
            'performed_action' => $validated['performed_action'],
            'expected_component_key' => $expectedItem->component_key,
            'performed_component_key' => $validated['component_key'],
            'source_zone' => $validated['source_zone'] ?? null,
            'destination_zone' => $validated['destination_zone'],
            'is_correct' => $isCorrect,
            'first_try' => $firstTry,
        ]);

        $summary = $this->refreshAttemptSummary($attempt, $expected);
        $phase = $this->phaseForAttempt($attempt->fresh(), $expected);

        return response()->json([
            'success' => true,
            'message' => $isCorrect
                ? 'Action recorded.'
                : 'Action recorded as incorrect.',
            'data' => [
                'allowed' => $isCorrect,
                'phase' => $phase,
                'completed_actions' => $summary['completed_steps'],
                'total_actions' => $summary['total_steps'],
                'mistake_count' => $summary['mistake_count'],
                'procedure_complete' => $summary['completed_steps'] === $summary['total_steps'],
            ],
        ]);
    }

    public function complete(Request $request, Quiz $quiz, DragDropAttempt $attempt)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');
        $this->assertAttemptOwner($attempt, $quiz, $user->id);

        if ($attempt->quiz_record_id) {
            return $this->completedAttemptResponse($attempt->fresh(), 200);
        }

        abort_if($attempt->steps()->count() === 0, 422, 'Perform at least one hardware action before submitting.');

        $expected = $this->procedureItems($attempt->question_id)->values();
        $summary = $this->refreshAttemptSummary($attempt, $expected);
        $passingScore = 75.00;
        $passed = $summary['completed_steps'] === $summary['total_steps']
            && $summary['percentage'] >= $passingScore;

        $savedAttempt = DB::transaction(function () use ($attempt, $quiz, $user, $summary, $passingScore, $passed) {
            $submittedAt = now();
            $elapsed = $attempt->created_at
                ? max(0, (int) $attempt->created_at->diffInSeconds($submittedAt))
                : null;

            $attemptNumber = ((int) QuizRecord::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->max('attempt_count')) + 1;

            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $summary['correct_steps'],
                'elapsed_time' => $elapsed,
                'correct_answers' => $summary['correct_steps'],
                'total_questions' => $summary['total_steps'],
                'percentage' => $summary['percentage'],
                'passing_score' => $passingScore,
                'status' => $passed ? 'passed' : 'failed',
                'attempt_count' => $attemptNumber,
                'quiz_type' => $quiz->quiz_type ?: 'hardware_procedure',
                'category' => $quiz->category ?: 'Computer Systems Servicing',
                'completed_at' => $submittedAt,
            ]);

            $attempt->update([
                'quiz_record_id' => $record->id,
                'completed_steps' => $summary['completed_steps'],
                'correct_steps' => $summary['correct_steps'],
                'total_steps' => $summary['total_steps'],
                'mistake_count' => $summary['mistake_count'],
                'percentage' => $summary['percentage'],
                'passed' => $passed,
                'elapsed_time' => $elapsed,
                'completed_at' => $submittedAt,
            ]);

            return $attempt->fresh();
        });

        return $this->completedAttemptResponse($savedAttempt, 201, $passingScore);
    }

    public function record(Request $request, QuizRecord $record)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless((int) $record->user_id === (int) $user->id, 403, 'You cannot view this record.');

        $attempt = DragDropAttempt::query()
            ->where('quiz_record_id', $record->id)
            ->with(['steps.expectedItem', 'quiz', 'question'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'record' => $record,
                'attempt' => [
                    'id' => $attempt->id,
                    'completed_steps' => (int) $attempt->completed_steps,
                    'correct_steps' => (int) $attempt->correct_steps,
                    'total_steps' => (int) $attempt->total_steps,
                    'mistake_count' => (int) $attempt->mistake_count,
                    'percentage' => (float) $attempt->percentage,
                    'passed' => (bool) $attempt->passed,
                    'elapsed_time' => $attempt->elapsed_time,
                    'completed_at' => $attempt->completed_at,
                    'actions' => $attempt->steps->map(fn($step) => [
                        'sequence_number' => (int) $step->sequence_number,
                        'phase' => $step->phase,
                        'expected_action' => $step->expected_action,
                        'performed_action' => $step->performed_action,
                        'expected_component_key' => $step->expected_component_key,
                        'performed_component_key' => $step->performed_component_key,
                        'source_zone' => $step->source_zone,
                        'destination_zone' => $step->destination_zone,
                        'is_correct' => (bool) $step->is_correct,
                        'first_try' => (bool) $step->first_try,
                    ])->values(),
                ],
            ],
        ]);
    }

    private function questionForQuiz(Quiz $quiz): Question
    {
        $questionId = DragDropItem::query()
            ->join('questions', 'questions.id', '=', 'drag_drop_items.question_id')
            ->where('questions.quiz_id', $quiz->id)
            ->where('drag_drop_items.active', true)
            ->where('drag_drop_items.required', true)
            ->whereNotNull('drag_drop_items.component_key')
            ->whereNotNull('drag_drop_items.target_zone')
            ->value('questions.id');

        abort_if(
            !$questionId,
            404,
            "No hardware question with configured practical items exists for quiz {$quiz->id}."
        );

        return Question::findOrFail($questionId);
    }

    private function procedureItems(int $questionId)
    {
        return DragDropItem::query()
            ->where('question_id', $questionId)
            ->where('active', true)
            ->where('required', true)
            ->whereNotNull('component_key')
            ->whereNotNull('target_zone')
            ->whereIn('phase', [
                'disassemble',
                'assemble',
            ])
            ->orderByRaw("
            CASE
                WHEN phase = 'disassemble' THEN 1
                WHEN phase = 'assemble' THEN 2
                ELSE 3
            END
        ")
            ->orderBy('correct_position')
            ->get();
    }

    private function phaseForAttempt(DragDropAttempt $attempt, $expected): string
    {
        $completed = (int) $attempt->steps()->where('is_correct', true)->count();
        $next = $expected->get($completed);

        return $next?->phase ?: 'complete';
    }

    private function refreshAttemptSummary(DragDropAttempt $attempt, $expected): array
    {
        $completedSteps = (int) $attempt->steps()->where('is_correct', true)->count();
        $correctSteps = (int) $attempt->steps()->where('is_correct', true)->where('first_try', true)->count();
        $mistakeCount = (int) $attempt->steps()->where('is_correct', false)->count();
        $totalSteps = $expected->count();
        $percentage = round(($correctSteps / max(1, $totalSteps)) * 100, 2);

        $attempt->update([
            'completed_steps' => $completedSteps,
            'correct_steps' => $correctSteps,
            'total_steps' => $totalSteps,
            'mistake_count' => $mistakeCount,
            'percentage' => $percentage,
        ]);

        return [
            'completed_steps' => $completedSteps,
            'correct_steps' => $correctSteps,
            'total_steps' => $totalSteps,
            'mistake_count' => $mistakeCount,
            'percentage' => $percentage,
        ];
    }

    private function assertAttemptOwner(DragDropAttempt $attempt, Quiz $quiz, int $userId): void
    {
        abort_unless((int) $attempt->quiz_id === (int) $quiz->id, 404, 'Attempt not found for this quiz.');
        abort_unless((int) $attempt->user_id === $userId, 403, 'You cannot use this attempt.');
    }

    private function completedAttemptResponse(DragDropAttempt $attempt, int $statusCode, float $passingScore = 75.00)
    {
        return response()->json([
            'success' => true,
            'message' => $attempt->passed
                ? 'Hardware practical assessment passed and recorded.'
                : 'Hardware practical assessment recorded.',
            'data' => [
                'record_id' => $attempt->quiz_record_id,
                'attempt_id' => $attempt->id,
                'completed_actions' => (int) $attempt->completed_steps,
                'correct_actions' => (int) $attempt->correct_steps,
                'total_actions' => (int) $attempt->total_steps,
                'mistake_count' => (int) $attempt->mistake_count,
                'percentage' => (float) $attempt->percentage,
                'passing_score' => $passingScore,
                'passed' => (bool) $attempt->passed,
                'status' => $attempt->passed ? 'passed' : 'failed',
                'elapsed_time' => $attempt->elapsed_time,
            ],
        ], $statusCode);
    }
}
