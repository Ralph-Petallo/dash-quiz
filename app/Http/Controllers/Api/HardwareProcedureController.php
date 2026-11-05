<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\DragDropAttempt;
use App\Models\DragDropItem;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class HardwareProcedureController extends Controller
{
    private const HARDWARE_TYPE = 'hardware_procedure';
    private const QUESTION_TYPE = 'drag_drop';
    private const QUESTION_TEXT = 'Complete the computer system unit practical assessment.';
    private const PASSING_SCORE = 75.00;
    private const TIME_LIMIT_MINUTES = 10;
    private const MISTAKE_PENALTY = 0.5;
    private const PHASES = DragDropItem::PHASES;

    // Checklist before any part is touched.
    private const PREP_STEPS = [
        'power_off' => 'Turn off the computer',
        'unplug_power' => 'Unplug the power cord from the wall outlet',
        'disconnect_peripherals' => 'Disconnect monitor, keyboard, mouse, and network cable',
        'gather_tools' => 'Gather a Phillips screwdriver and a screw container',
        'prepare_workspace' => 'Move the system unit to a clean, non-carpeted table',
        'wear_wrist_strap' => 'Put on the anti-static wrist strap',
        'ground_wrist_strap' => 'Clip the strap to a grounded metal surface',
    ];

    // Checklist after the chassis is closed.
    private const FINISH_STEPS = [
        'reconnect_peripherals' => 'Reconnect monitor, keyboard, mouse, and network cable',
        'plug_power' => 'Plug in the power cord and switch on the power supply',
        'power_on_test' => 'Turn on the computer and check that it starts',
    ];

    // Chassis parts. Used for disassembly and assembly.
    private const COMPONENTS = [
        'side_panel' => 'Side panel',
        'internal_cables' => 'Internal cables and connectors',
        'system_fan' => 'System fan',
        'expansion_card' => 'Expansion card',
        'ram' => 'RAM',
        'cpu_heatsink' => 'CPU heatsink',
        'storage_drive' => 'Storage drive',
        'psu' => 'Power supply',
        'motherboard' => 'Motherboard',
    ];

    /*
    |==========================================================================
    | USER (LEARNER) METHODS
    |==========================================================================
    */

    /** Return the learner-safe practical details. Answers and step order stay on the server. */
    public function show(Quiz $quiz): JsonResponse
    {
        $assessment = $this->assessmentForQuiz($quiz);
        $question = $this->questionForQuiz($quiz);
        $items = $this->procedureItems($question->id);

        return response()->json([
            'success' => true,
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'coc_number' => $quiz->coc_number,
                ],
                'assessment' => $this->learnerAssessment($assessment),
                'prep_steps' => $this->stepCards($items, DragDropItem::PREPARE),
                'finish_steps' => $this->stepCards($items, DragDropItem::FINISH),
                'components' => $this->componentCards($items),
                'total_actions' => $items->count(),
                'passing_score' => self::PASSING_SCORE,
            ],
        ]);
    }

    /** Resume the open attempt, or create a new timed attempt. */
    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();
        $question = $this->questionForQuiz($quiz);
        $items = $this->procedureItems($question->id);

        abort_if($items->isEmpty(), 409, 'The hardware procedure has no configured steps.');

        $this->closeExpiredAttempts($user->id, $quiz->id, $question->id);

        $attempt = DragDropAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('question_id', $question->id)
            ->whereNull('completed_at')
            ->whereNull('quiz_record_id')
            ->latest('id')
            ->first()
            ?? DragDropAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'completed_steps' => 0,
                'correct_steps' => 0,
                'total_steps' => $items->count(),
                'mistake_count' => 0,
                'percentage' => 0,
                'passed' => false,
                'elapsed_time' => 0,
                'expires_at' => now()->addMinutes(self::TIME_LIMIT_MINUTES),
            ]);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                [
                    'attempt_id' => $attempt->id,
                    'remaining_seconds' => $this->remainingSeconds($attempt),
                    'started_at' => $attempt->created_at,
                ],
                $this->progress($attempt, $items)
            ),
        ]);
    }

    /** Record the learner's next move and return the updated progress. */
    public function action(Request $request, Quiz $quiz, DragDropAttempt $attempt): JsonResponse
    {
        $this->assertOwner($attempt, $quiz, $request->user()->id);

        $validated = $request->validate([
            'component_key' => ['required', 'string', 'max:50'],
            'performed_action' => ['required', 'string', 'max:20'],
            'source_zone' => ['nullable', 'string', 'max:20'],
            'destination_zone' => ['required', 'string', 'max:20'],
        ]);

        $outcome = DB::transaction(function () use ($attempt, $validated) {
            $locked = $this->lockOpenAttempt($attempt);
            $items = $this->procedureItems($locked->question_id);

            $correct = $locked->steps()->where('is_correct', true)->count();
            $expected = $items->get($correct);

            // Procedure already finished. Nothing more to record.
            if (!$expected) {
                return [
                    'allowed' => false,
                    'progress' => $this->progress($locked, $items),
                ];
            }

            abort_unless(
                $items->contains('component_key', $validated['component_key']),
                422,
                'Unknown hardware component.'
            );

            // Checklist steps only need the right key. Parts need the right action and zone too.
            $isCorrect = $validated['component_key'] === $expected->component_key
                && ($expected->isChecklist()
                    || ($validated['performed_action'] === $expected->action
                        && $validated['destination_zone'] === $expected->target_zone));

            // Check first-try status before saving this step.
            $firstTry = !$locked->steps()
                ->where('expected_item_id', $expected->id)
                ->exists();

            $locked->steps()->create([
                'expected_item_id' => $expected->id,
                'sequence_number' => (int) $locked->steps()->max('sequence_number') + 1,
                'expected_position' => (int) $expected->correct_position,
                'phase' => $expected->phase,
                'expected_action' => $expected->action,
                'performed_action' => $validated['performed_action'],
                'expected_component_key' => $expected->component_key,
                'performed_component_key' => $validated['component_key'],
                'source_zone' => $validated['source_zone'] ?? null,
                'destination_zone' => $validated['destination_zone'],
                'is_correct' => $isCorrect,
                'first_try' => $firstTry,
            ]);

            return [
                'allowed' => $isCorrect,
                'progress' => $this->progress($locked, $items),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $outcome['allowed']
                ? 'Action recorded successfully.'
                : 'Action recorded as incorrect. Follow the proper procedure sequence.',
            'data' => array_merge(
                ['allowed' => $outcome['allowed']],
                $outcome['progress']
            ),
        ]);
    }

    /** Score a fully completed attempt and save it to the learner's records. */
    public function complete(Request $request, Quiz $quiz, DragDropAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        $this->assertOwner($attempt, $quiz, $user->id);

        $outcome = DB::transaction(function () use ($attempt, $quiz, $user) {
            $locked = $this->lockOpenAttempt($attempt);
            $items = $this->procedureItems($locked->question_id);
            $progress = $this->progress($locked, $items);

            if ($progress['completed_actions'] < $progress['total_actions']) {
                return [
                    'incomplete' => $progress['total_actions'] - $progress['completed_actions'],
                ];
            }

            $passed = $progress['percentage'] >= self::PASSING_SCORE;
            $elapsed = (int) $locked->created_at->diffInSeconds(now());

            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $progress['score'],
                'elapsed_time' => $elapsed,
                'correct_answers' => $progress['completed_actions'],
                'total_questions' => $progress['total_actions'],
                'percentage' => $progress['percentage'],
                'passing_score' => self::PASSING_SCORE,
                'status' => $passed ? 'passed' : 'failed',
                'attempt_count' => QuizRecord::query()
                    ->where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->count() + 1,
                'quiz_type' => self::HARDWARE_TYPE,
                'category' => $quiz->category ?: 'Computer Maintenance and Troubleshooting',
                'completed_at' => now(),
            ]);

            $locked->update([
                'quiz_record_id' => $record->id,
                'completed_at' => now(),
                'passed' => $passed,
                'elapsed_time' => $elapsed,
            ]);

            return [
                'passed' => $passed,
                'record_id' => $record->id,
                'progress' => $progress,
            ];
        });

        if (isset($outcome['incomplete'])) {
            return response()->json([
                'success' => false,
                'message' => 'Complete all required procedure steps before submitting.',
                'remaining' => $outcome['incomplete'],
            ], 422);
        }

        $progress = $outcome['progress'];

        return response()->json([
            'success' => true,
            'message' => $outcome['passed']
                ? 'Practical assessment passed!'
                : 'Practical assessment completed.',
            'data' => [
                'attempt_id' => $attempt->id,
                'record_id' => $outcome['record_id'],
                'passed' => $outcome['passed'],
                'percentage' => $progress['percentage'],
                'completed_actions' => $progress['completed_actions'],
                'total_actions' => $progress['total_actions'],
                'mistake_count' => $progress['mistake_count'],
            ],
        ]);
    }

    /** Return a saved practical result, only to the learner who owns it. */
    public function record(Request $request, int $recordId): JsonResponse
    {
        $record = QuizRecord::query()
            ->with(['quiz', 'hardwareProcedureAttempt.steps'])
            ->whereKey($recordId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_if(!$record, 404, 'Assessment record not found.');

        return response()->json([
            'success' => true,
            'data' => [
                'record' => $record,
                'attempt' => $record->hardwareProcedureAttempt,
                'steps' => $record->hardwareProcedureAttempt?->steps ?? [],
            ],
        ]);
    }

    /** Find the hardware assessment for a COC. */
    private function assessmentForQuiz(Quiz $quiz): Assessment
    {
        $assessment = Assessment::query()
            ->where('coc_id', $quiz->id)
            ->where('type', self::HARDWARE_TYPE)
            ->first();

        abort_if(!$assessment, 404, 'Hardware procedure assessment not found.');

        return $assessment;
    }

    /** Keep only the assessment fields a learner needs. */
    private function learnerAssessment(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'type' => $assessment->type,
            'icon' => $assessment->icon,
            'description' => $assessment->description,
            'category' => $assessment->category,
            'difficulty' => $assessment->difficulty,
        ];
    }

    /** Find the first configured procedure question for a COC. */
    private function questionForQuiz(Quiz $quiz): Question
    {
        $question = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereHas('dragDropItems', fn($query) => $query->procedure())
            ->orderBy('id')
            ->first();

        abort_if(!$question, 404, 'No configured hardware question exists for this quiz.');

        return $question;
    }

    /** Load the procedure in scoring order: prepare, disassemble, assemble, then finish. */
    private function procedureItems(int $questionId): Collection
    {
        return DragDropItem::query()
            ->where('question_id', $questionId)
            ->procedure()
            ->orderByRaw("CASE phase WHEN 'prepare' THEN 1 WHEN 'disassemble' THEN 2 WHEN 'assemble' THEN 3 ELSE 4 END")
            ->orderBy('correct_position')
            ->orderBy('id')
            ->get();
    }

    /** Checklist cards in alphabetical order, so the list never reveals the correct sequence. */
    private function stepCards(Collection $items, string $phase): Collection
    {
        return $items
            ->where('phase', $phase)
            ->unique('component_key')
            ->sortBy('display_name')
            ->map(fn(DragDropItem $item) => [
                'component_key' => $item->component_key,
                'display_name' => $item->display_name,
            ])
            ->values();
    }

    /** Chassis part cards for display. */
    private function componentCards(Collection $items): Collection
    {
        return $items
            ->filter(fn(DragDropItem $item) => $item->isPart())
            ->unique('component_key')
            ->sortBy('display_name')
            ->map(fn(DragDropItem $item) => [
                'component_key' => $item->component_key,
                'display_name' => $item->display_name,
            ])
            ->values();
    }

    /**
     * Recalculate progress and save it to the attempt.
     * Score = correct steps minus half a step per mistake, never below 0.
     */
    private function progress(DragDropAttempt $attempt, Collection $items): array
    {
        $correct = $attempt->steps()->where('is_correct', true)->count();
        $mistakes = $attempt->steps()->where('is_correct', false)->count();
        $total = $items->count();

        $score = max(0, $correct - $mistakes * self::MISTAKE_PENALTY);
        $percentage = $total > 0 ? round($score / $total * 100, 2) : 0;

        $attempt->update([
            'completed_steps' => $correct,
            'correct_steps' => $correct,
            'total_steps' => $total,
            'mistake_count' => $mistakes,
            'percentage' => $percentage,
        ]);

        $next = $items->get($correct);
        $done = $items->take($correct);

        return [
            'completed_actions' => $correct,
            'total_actions' => $total,
            'mistake_count' => $mistakes,
            'score' => $score,
            'percentage' => $percentage,
            'phase' => $next?->phase ?? 'complete',
            'procedure_complete' => $next === null,
            'checklist_done' => $done
                ->filter(fn(DragDropItem $item) => $item->isChecklist())
                ->pluck('component_key')
                ->values()
                ->all(),
            'installed_components' => $this->installedComponents($items, $correct),
        ];
    }

    /**
     * Parts start in the chassis. Correct disassembly steps remove them,
     * and correct assembly steps put them back. Checklist steps change nothing.
     */
    private function installedComponents(Collection $items, int $correct): array
    {
        $parts = $items->filter(fn(DragDropItem $item) => $item->isPart());
        $installed = array_fill_keys($parts->pluck('component_key')->unique()->all(), true);

        foreach ($items->take($correct) as $item) {
            if ($item->phase === DragDropItem::DISASSEMBLE) {
                unset($installed[$item->component_key]);
            }

            if ($item->phase === DragDropItem::ASSEMBLE) {
                $installed[$item->component_key] = true;
            }
        }

        return array_keys($installed);
    }

    /** Seconds left, from the server-side expiry time. */
    private function remainingSeconds(DragDropAttempt $attempt): int
    {
        if (!$attempt->expires_at) {
            return self::TIME_LIMIT_MINUTES * 60;
        }

        return max(0, $attempt->expires_at->timestamp - now()->timestamp);
    }

    /** Close expired open attempts so the learner can start a new one. */
    private function closeExpiredAttempts(int $userId, int $quizId, int $questionId): void
    {
        DragDropAttempt::query()
            ->where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('question_id', $questionId)
            ->whereNull('completed_at')
            ->whereNull('quiz_record_id')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '<=', now());
            })
            ->update(['completed_at' => now()]);
    }

    /** Lock the attempt row and reject it if it is submitted or expired. */
    private function lockOpenAttempt(DragDropAttempt $attempt): DragDropAttempt
    {
        $locked = DragDropAttempt::query()
            ->whereKey($attempt->id)
            ->lockForUpdate()
            ->firstOrFail();

        abort_if(
            $locked->completed_at || $locked->quiz_record_id,
            409,
            'This attempt has already been submitted.'
        );

        abort_if(
            $locked->expires_at && $locked->expires_at->isPast(),
            410,
            'This practical attempt has expired. Please start a new attempt.'
        );

        return $locked;
    }

    /** Make sure the attempt belongs to this learner and this COC. */
    private function assertOwner(DragDropAttempt $attempt, Quiz $quiz, int $userId): void
    {
        abort_unless(
            (int) $attempt->user_id === $userId && (int) $attempt->quiz_id === (int) $quiz->id,
            403,
            'This practical attempt does not belong to you.'
        );
    }

    /*
    |==========================================================================
    | ADMIN METHODS
    |==========================================================================
    */

    /** Create the hardware assessment for a COC if it does not exist yet. */
    public function adminStore(Request $request, int $cocId): JsonResponse
    {
        $this->ensureAdmin($request);

        $quiz = Quiz::query()->findOrFail($cocId);

        $assessment = Assessment::query()->firstOrCreate(
            ['coc_id' => $quiz->id, 'type' => self::HARDWARE_TYPE],
            [
                'title' => 'Assembly and Disassembly',
                'category' => $quiz->category ?? 'Hardware',
                'available' => true,
                'path' => '/taking/hardware-procedure',
            ]
        );

        return response()->json([
            'status' => 'success',
            'assessment_id' => $assessment->id,
        ], $assessment->wasRecentlyCreated ? 201 : 200);
    }

    /** Return the assessment, its ordered steps, and the catalogs the editor offers. */
    public function edit(Request $request, int $assessmentId): JsonResponse
    {
        $this->ensureAdmin($request);

        $assessment = Assessment::query()
            ->where('type', self::HARDWARE_TYPE)
            ->findOrFail($assessmentId);

        $question = $this->adminQuestion($assessment);

        $steps = $question
            ? DragDropItem::query()
                ->where('question_id', $question->id)
                ->orderByRaw("CASE phase WHEN 'prepare' THEN 1 WHEN 'disassemble' THEN 2 WHEN 'assemble' THEN 3 ELSE 4 END")
                ->orderBy('correct_position')
                ->orderBy('id')
                ->get()
                ->map(fn(DragDropItem $item) => [
                    'id' => $item->id,
                    'phase' => $item->phase,
                    'component_key' => $item->component_key,
                    'display_name' => $item->display_name,
                    'action' => $item->action,
                    'target_zone' => $item->target_zone,
                    'instruction' => $item->instruction,
                    'correct_position' => (int) $item->correct_position,
                ])
            : collect();

        return response()->json([
            'status' => 'success',
            'quiz' => Quiz::query()->find($assessment->coc_id),
            'assessment' => $assessment,
            'steps' => $steps->values(),
            'catalogs' => [
                'prepare' => $this->catalogList(self::PREP_STEPS),
                'disassemble' => $this->catalogList(self::COMPONENTS),
                'assemble' => $this->catalogList(self::COMPONENTS),
                'finish' => $this->catalogList(self::FINISH_STEPS),
            ],
            'has_attempts' => $question
                ? DragDropAttempt::query()->where('question_id', $question->id)->exists()
                : false,
        ]);
    }

    /**
     * Save the ordered procedure. Action and target zone are derived here,
     * so the editor cannot save a step the practical would reject.
     */
    public function update(Request $request, int $assessmentId): JsonResponse
    {
        $this->ensureAdmin($request);

        $assessment = Assessment::query()
            ->where('type', self::HARDWARE_TYPE)
            ->findOrFail($assessmentId);

        $validated = $request->validate([
            'steps' => ['required', 'array', 'min:2'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.phase' => ['required', Rule::in(self::PHASES)],
            'steps.*.component_key' => ['required', Rule::in($this->allStepKeys())],
            'steps.*.instruction' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertProcedureShape($validated['steps']);

        $question = $this->adminQuestion($assessment);
        $existing = $question
            ? DragDropItem::query()->where('question_id', $question->id)->get()
            : collect();
        $hasAttempts = $question
            ? DragDropAttempt::query()->where('question_id', $question->id)->exists()
            : false;
        $incoming = $this->normalizedSteps($validated['steps']);

        // After the first attempt, the order and parts are locked. Only instructions can change.
        $shapeChanged = $this->shapeOf($existing->map(fn($item) => $this->rowOf($item)))
            !== $this->shapeOf(collect($incoming));
        $hasNewSteps = collect($incoming)->contains(fn(array $row) => empty($row['id']));

        if ($hasAttempts && ($shapeChanged || $hasNewSteps)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Learners have already attempted this practical, so its steps and order are locked. Only instructions can be changed.',
            ], 409);
        }

        try {
            DB::transaction(fn() => $this->saveSteps($assessment, $question, $existing, $incoming, $hasAttempts));

            return response()->json([
                'status' => 'success',
                'message' => 'Hardware procedure updated successfully.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the hardware procedure.',
            ], 500);
        }
    }

    /** Stop non-admins before any admin work starts. */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');
    }

    /** The procedure question for this assessment's COC, if one exists. */
    private function adminQuestion(Assessment $assessment): ?Question
    {
        return Question::query()
            ->where('quiz_id', $assessment->coc_id)
            ->whereHas('dragDropItems')
            ->orderBy('id')
            ->first();
    }

    /** Write the steps, creating the question if needed. */
    private function saveSteps(
        Assessment $assessment,
        ?Question $question,
        Collection $existing,
        array $incoming,
        bool $hasAttempts
    ): void {
        $target = $question ?? Question::query()->create([
            'quiz_id' => $assessment->coc_id,
            'question_type' => self::QUESTION_TYPE,
            'question_text' => self::QUESTION_TEXT,
        ]);

        $keepIds = [];

        foreach ($incoming as $row) {
            $item = $row['id'] ? $existing->firstWhere('id', (int) $row['id']) : null;

            if ($row['id'] && !$item) {
                throw ValidationException::withMessages([
                    'steps' => ['A step does not belong to this practical.'],
                ]);
            }

            $item ??= new DragDropItem;
            $item->fill([
                'question_id' => $target->id,
                'component_key' => $row['component_key'],
                'display_name' => $row['display_name'],
                'item_type' => $row['item_type'],
                'quantity' => 1,
                'item_text' => $row['display_name'],
                'instruction' => $row['instruction'],
                'action' => $row['action'],
                'target_zone' => $row['target_zone'],
                'phase' => $row['phase'],
                'correct_position' => $row['correct_position'],
                'required' => true,
                'active' => true,
            ]);
            $item->save();

            $keepIds[] = $item->id;
        }

        // Remove unused steps only when no learner has attempted them.
        if (!$hasAttempts) {
            DragDropItem::query()
                ->where('question_id', $target->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    /** Catalog for a phase. Prepare and finish use checklist labels, the others use parts. */
    private function catalogFor(string $phase): array
    {
        return match ($phase) {
            DragDropItem::PREPARE => self::PREP_STEPS,
            DragDropItem::FINISH => self::FINISH_STEPS,
            default => self::COMPONENTS,
        };
    }

    /** Every key the admin may use, across all phases. */
    private function allStepKeys(): array
    {
        return array_values(array_unique([
            ...array_keys(self::PREP_STEPS),
            ...array_keys(self::COMPONENTS),
            ...array_keys(self::FINISH_STEPS),
        ]));
    }

    /** Turn a catalog into a list of key and label pairs for the editor. */
    private function catalogList(array $catalog): array
    {
        return collect($catalog)
            ->map(fn(string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /** The action and destination a step must use, derived from its phase. */
    private function rulesForStep(string $phase, string $componentKey): array
    {
        return match ($phase) {
            DragDropItem::PREPARE, DragDropItem::FINISH => ['action' => 'perform', 'target_zone' => 'none'],
            DragDropItem::DISASSEMBLE => [
                'action' => 'remove',
                'target_zone' => $componentKey === 'internal_cables' ? 'cable_tray' : 'parts_tray',
            ],
            default => ['action' => 'install', 'target_zone' => 'case'],
        };
    }

    /** Add labels, actions, zones, and positions to the submitted steps. */
    private function normalizedSteps(array $steps): array
    {
        $positions = array_fill_keys(self::PHASES, 0);
        $rows = [];

        foreach ($steps as $step) {
            $phase = $step['phase'];
            $key = $step['component_key'];
            $rules = $this->rulesForStep($phase, $key);

            $positions[$phase]++;

            $rows[] = [
                'id' => $step['id'] ?? null,
                'phase' => $phase,
                'component_key' => $key,
                'display_name' => $this->catalogFor($phase)[$key],
                'item_type' => match ($phase) {
                    DragDropItem::PREPARE => 'prep',
                    DragDropItem::FINISH => 'finish',
                    default => 'component',
                },
                'action' => $rules['action'],
                'target_zone' => $rules['target_zone'],
                'instruction' => $step['instruction'] ?? null,
                'correct_position' => $positions[$phase],
            ];
        }

        return $rows;
    }

    /** Check the phase order, and that each phase has every item exactly once. */
    private function assertProcedureShape(array $steps): void
    {
        $errors = [];
        $rank = array_flip(self::PHASES);
        $previous = 0;

        foreach ($steps as $index => $step) {
            $current = $rank[$step['phase']];

            if ($current < $previous) {
                $errors["steps.$index.phase"] = ['Steps must follow the order: prepare, disassemble, assemble, then finish.'];
            }

            $previous = max($previous, $current);
        }

        foreach (self::PHASES as $phase) {
            $catalog = $this->catalogFor($phase);
            $keys = collect($steps)->where('phase', $phase)->pluck('component_key')->values();

            $invalid = array_diff($keys->all(), array_keys($catalog));
            $missing = array_diff(array_keys($catalog), $keys->all());
            $duplicates = $keys->duplicates()->unique()->values()->all();

            if ($invalid) {
                $errors['steps'][] = ucfirst($phase) . ' has steps that do not belong there: ' . $this->labels($phase, $invalid) . '.';
            }

            if ($missing) {
                $errors['steps'][] = ucfirst($phase) . ' is missing: ' . $this->labels($phase, $missing) . '.';
            }

            if ($duplicates) {
                $errors['steps'][] = ucfirst($phase) . ' uses a step more than once: ' . $this->labels($phase, $duplicates) . '.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** Readable labels for validation messages. */
    private function labels(string $phase, array $keys): string
    {
        $catalog = $this->catalogFor($phase);

        return implode(', ', array_map(fn(string $key) => $catalog[$key] ?? $key, $keys));
    }

    /** The fields that define a saved step's shape. */
    private function rowOf(DragDropItem $item): array
    {
        return [
            'phase' => $item->phase,
            'component_key' => $item->component_key,
            'action' => $item->action,
            'target_zone' => $item->target_zone,
            'correct_position' => (int) $item->correct_position,
        ];
    }

    /** A signature of the procedure's shape, ignoring IDs and instructions. */
    private function shapeOf(Collection $rows): string
    {
        return $rows
            ->sortBy(fn(array $row) => sprintf(
                '%d-%03d',
                array_search($row['phase'], self::PHASES, true),
                $row['correct_position']
            ))
            ->map(fn(array $row) => implode('|', [
                $row['phase'],
                $row['component_key'],
                $row['action'],
                $row['target_zone'],
            ]))
            ->implode(';');
    }
}