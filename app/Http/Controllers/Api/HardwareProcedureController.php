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

    private const PASSING_SCORE = 75.00;

    private const TIME_LIMIT_MINUTES = 10;

    private const MISTAKE_PENALTY = 0.5;

    private const PHASES = ['disassemble', 'assemble'];

    // Keys must match the component keys used by the Vue practical.
    private const ADMIN_COMPONENTS = [
        'side_panel' => 'Side panel',
        'internal_cables' => 'Internal cables',
        'system_fan' => 'System fan',
        'expansion_card' => 'Expansion card',
        'ram' => 'RAM',
        'cpu_heatsink' => 'CPU heatsink',
        'storage_drive' => 'Storage drive',
        'psu' => 'Power supply',
        'motherboard' => 'Motherboard',
    ];

    // Actions and zones in the same group count as the same thing.
    private const ACTION_GROUPS = [
        ['remove', 'disconnect', 'unplug', 'unscrew'],
        ['install', 'connect', 'plug', 'screw'],
    ];

    private const ZONE_GROUPS = [
        ['parts_tray', 'cable_tray', 'tray'],
        ['case', 'chassis'],
    ];

    /*
    |--------------------------------------------------------------------------
    | LEARNER: SHOW
    |--------------------------------------------------------------------------
    */

    public function show(Quiz $quiz): JsonResponse
    {
        $assessment = Assessment::query()
            ->where('coc_id', $quiz->id)
            ->where('type', self::HARDWARE_TYPE)
            ->first();

        abort_if(! $assessment, 404, 'Hardware procedure assessment not found.');

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
                'assessment' => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'type' => $assessment->type,
                    'icon' => $assessment->icon,
                    'description' => $assessment->description,
                    'category' => $assessment->category,
                    'difficulty' => $assessment->difficulty,
                    'meta' => $assessment->meta,
                ],
                'question' => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                ],
                // One card per part for display only.
                'components' => $items
                    ->unique('component_key')
                    ->values()
                    ->map(fn (DragDropItem $item) => [
                        'component_key' => $item->component_key,
                        'display_name' => $item->display_name,
                        'item_type' => $item->item_type,
                        'quantity' => $item->quantity,
                        'image' => $item->item_image_path,
                    ])
                    ->values(),
                // Every step counts, including the assembly of each part.
                'total_actions' => $items->count(),
                'passing_score' => self::PASSING_SCORE,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LEARNER: START (resumes the open attempt or creates one)
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | LEARNER: ACTION (one pick-up or placement)
    |--------------------------------------------------------------------------
    */

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

            $correctSteps = $locked->steps()->where('is_correct', true)->count();
            $expected = $items->get($correctSteps);

            // Procedure already finished. Nothing more to record.
            if (! $expected) {
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

            $isCorrect =
                $validated['component_key'] === $expected->component_key
                && $this->actionMatches($validated['performed_action'], $expected->action)
                && $this->zoneMatches($validated['destination_zone'], $expected->target_zone);

            $firstTry = ! $locked->steps()
                ->where('expected_item_id', $expected->id)
                ->exists();

            $sequence = (int) $locked->steps()->max('sequence_number') + 1;

            $locked->steps()->create([
                'expected_item_id' => $expected->id,
                'sequence_number' => $sequence,
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

    /*
    |--------------------------------------------------------------------------
    | LEARNER: COMPLETE (submit the attempt)
    |--------------------------------------------------------------------------
    */

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

            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $progress['score'],
                'elapsed_time' => (int) $locked->created_at->diffInSeconds(now()),
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
                'elapsed_time' => (int) $locked->created_at->diffInSeconds(now()),
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

    /*
    |--------------------------------------------------------------------------
    | LEARNER: RECORD DETAILS
    |--------------------------------------------------------------------------
    */

    public function record(Request $request, int $recordId): JsonResponse
    {
        $record = QuizRecord::query()
            ->with(['quiz', 'hardwareProcedureAttempt.steps'])
            ->whereKey($recordId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_if(! $record, 404, 'Assessment record not found.');

        return response()->json([
            'success' => true,
            'data' => [
                'record' => $record,
                'attempt' => $record->hardwareProcedureAttempt,
                'steps' => $record->hardwareProcedureAttempt?->steps ?? [],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LEARNER HELPERS
    |--------------------------------------------------------------------------
    */

    private function questionForQuiz(Quiz $quiz): Question
    {
        $question = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereHas('dragDropItems', fn ($query) => $query
                ->where('active', true)
                ->where('required', true)
                ->whereNotNull('component_key')
                ->whereNotNull('target_zone'))
            ->orderBy('id')
            ->first();

        abort_if(! $question, 404, 'No configured hardware question exists for this quiz.');

        return $question;
    }

    private function procedureItems(int $questionId): Collection
    {
        return DragDropItem::query()
            ->where('question_id', $questionId)
            ->where('active', true)
            ->where('required', true)
            ->whereNotNull('component_key')
            ->whereNotNull('target_zone')
            ->whereIn('phase', self::PHASES)
            ->orderByRaw("CASE WHEN phase = 'disassemble' THEN 1 ELSE 2 END")
            ->orderBy('correct_position')
            ->orderBy('id')
            ->get();
    }

    /*
     * Builds everything the page needs after any change. Also saves the
     * summary to the attempt row. Score = correct steps minus half a step
     * per mistake, never below 0.
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

        return [
            'completed_actions' => $correct,
            'correct_actions' => $correct,
            'total_actions' => $total,
            'mistake_count' => $mistakes,
            'score' => $score,
            'percentage' => $percentage,
            'phase' => $next?->phase ?? 'complete',
            'procedure_complete' => $next === null,
            'installed_components' => $this->installedComponents($items, $correct),
        ];
    }

    // Every part starts in the case. Each correct disassembly step removes
    // a part, and each correct assembly step puts it back.
    private function installedComponents(Collection $items, int $correct): array
    {
        $installed = array_fill_keys($items->pluck('component_key')->unique()->all(), true);

        foreach ($items->take($correct) as $item) {
            if ($item->phase === 'disassemble') {
                unset($installed[$item->component_key]);
            } else {
                $installed[$item->component_key] = true;
            }
        }

        return array_keys($installed);
    }

    private function remainingSeconds(DragDropAttempt $attempt): int
    {
        if (! $attempt->expires_at) {
            return self::TIME_LIMIT_MINUTES * 60;
        }

        return max(0, $attempt->expires_at->timestamp - now()->timestamp);
    }

    // Closes timed-out attempts so they can't block a new one.
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

    private function assertOwner(DragDropAttempt $attempt, Quiz $quiz, int $userId): void
    {
        abort_unless(
            (int) $attempt->user_id === $userId && (int) $attempt->quiz_id === (int) $quiz->id,
            403,
            'This practical attempt does not belong to you.'
        );
    }

    private function actionMatches(string $performed, string $expected): bool
    {
        return $this->sameGroup($performed, $expected, self::ACTION_GROUPS);
    }

    private function zoneMatches(string $destination, string $expected): bool
    {
        return $this->sameGroup($destination, $expected, self::ZONE_GROUPS);
    }

    private function sameGroup(string $a, string $b, array $groups): bool
    {
        if ($a === $b) {
            return true;
        }

        foreach ($groups as $group) {
            if (in_array($a, $group, true) && in_array($b, $group, true)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: CREATE
    |--------------------------------------------------------------------------
    */

    public function adminStore(Request $request, int $cocId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $quiz = Quiz::query()->findOrFail($cocId);

        $assessment = Assessment::query()->firstOrCreate(
            [
                'coc_id' => $quiz->id,
                'type' => self::HARDWARE_TYPE,
            ],
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

    /*
    |--------------------------------------------------------------------------
    | ADMIN: EDIT (load steps)
    |--------------------------------------------------------------------------
    */

    public function edit(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $assessment = Assessment::query()
            ->where('type', self::HARDWARE_TYPE)
            ->findOrFail($assessmentId);

        $question = $this->adminQuestion($assessment);

        $steps = $question
            ? DragDropItem::query()
                ->where('question_id', $question->id)
                ->orderByRaw("CASE WHEN phase = 'disassemble' THEN 1 ELSE 2 END")
                ->orderBy('correct_position')
                ->orderBy('id')
                ->get()
                ->map(fn (DragDropItem $item) => [
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
            'question' => $question,
            'steps' => $steps->values(),
            'components' => collect(self::ADMIN_COMPONENTS)
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'has_attempts' => $question
                ? DragDropAttempt::query()->where('question_id', $question->id)->exists()
                : false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: UPDATE (save steps)
    |--------------------------------------------------------------------------
    | Steps arrive in display order. Action and target zone are derived here,
    | so the editor can't save a step the practical would reject.
    */

    public function update(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $assessment = Assessment::query()
            ->where('type', self::HARDWARE_TYPE)
            ->findOrFail($assessmentId);

        $validated = $request->validate([
            'steps' => ['required', 'array', 'min:2'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.phase' => ['required', Rule::in(self::PHASES)],
            'steps.*.component_key' => ['required', Rule::in(array_keys(self::ADMIN_COMPONENTS))],
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

        // Once learners have attempted it, the order and parts are locked.
        $shapeChanged = $this->shapeOf($existing->map(fn ($item) => $this->rowOf($item)))
            !== $this->shapeOf(collect($incoming));
        $hasNewSteps = collect($incoming)->contains(fn (array $row) => empty($row['id']));

        if ($hasAttempts && ($shapeChanged || $hasNewSteps)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Learners have already attempted this practical, so its steps and order are locked. Only instructions can be changed.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($assessment, $question, $existing, $hasAttempts, $incoming) {
                $target = $question ?? Question::query()->create([
                    'quiz_id' => $assessment->coc_id,
                    'question_type' => self::HARDWARE_TYPE,
                    'question_text' => 'Disassemble and reassemble the system unit.',
                ]);

                $keepIds = [];

                foreach ($incoming as $row) {
                    $item = $row['id']
                        ? $existing->firstWhere('id', (int) $row['id'])
                        : null;

                    if ($row['id'] && ! $item) {
                        throw ValidationException::withMessages([
                            'steps' => ['A step does not belong to this practical.'],
                        ]);
                    }

                    $item ??= new DragDropItem;
                    $item->question_id = $target->id;
                    $item->fill([
                        'component_key' => $row['component_key'],
                        'display_name' => $row['display_name'],
                        'item_type' => 'component',
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

                // Unused steps are removed only when no learner has attempted them.
                if (! $hasAttempts) {
                    DragDropItem::query()
                        ->where('question_id', $target->id)
                        ->whereNotIn('id', $keepIds)
                        ->delete();
                }
            });

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

    /*
    |--------------------------------------------------------------------------
    | ADMIN HELPERS
    |--------------------------------------------------------------------------
    */

    private function adminQuestion(Assessment $assessment): ?Question
    {
        return Question::query()
            ->where('quiz_id', $assessment->coc_id)
            ->whereHas('dragDropItems')
            ->orderBy('id')
            ->first();
    }

    private function rulesForStep(string $phase, string $componentKey): array
    {
        if ($phase === 'disassemble') {
            return [
                'action' => 'remove',
                'target_zone' => $componentKey === 'internal_cables' ? 'cable_tray' : 'parts_tray',
            ];
        }

        return [
            'action' => 'install',
            'target_zone' => 'case',
        ];
    }

    private function normalizedSteps(array $steps): array
    {
        $positions = ['disassemble' => 0, 'assemble' => 0];
        $rows = [];

        foreach ($steps as $step) {
            $phase = $step['phase'];
            $component = $step['component_key'];
            $rules = $this->rulesForStep($phase, $component);

            $positions[$phase]++;

            $rows[] = [
                'id' => $step['id'] ?? null,
                'phase' => $phase,
                'component_key' => $component,
                'display_name' => self::ADMIN_COMPONENTS[$component],
                'action' => $rules['action'],
                'target_zone' => $rules['target_zone'],
                'instruction' => $step['instruction'] ?? null,
                'correct_position' => $positions[$phase],
            ];
        }

        return $rows;
    }

    private function assertProcedureShape(array $steps): void
    {
        $errors = [];
        $sawAssemble = false;

        foreach ($steps as $index => $step) {
            if ($step['phase'] === 'assemble') {
                $sawAssemble = true;
            } elseif ($sawAssemble) {
                $errors["steps.$index.phase"] = ['Disassemble steps must come before assemble steps.'];
            }
        }

        foreach (self::PHASES as $phase) {
            $keys = collect($steps)->where('phase', $phase)->pluck('component_key');

            $missing = array_diff(array_keys(self::ADMIN_COMPONENTS), $keys->all());
            $duplicates = $keys->duplicates()->unique()->values()->all();

            if ($missing) {
                $errors['steps'][] = ucfirst($phase).' is missing: '.$this->labels($missing).'.';
            }

            if ($duplicates) {
                $errors['steps'][] = ucfirst($phase).' uses a component more than once: '.$this->labels($duplicates).'.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function labels(array $keys): string
    {
        return implode(', ', array_map(fn (string $key) => self::ADMIN_COMPONENTS[$key], $keys));
    }

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

    // A readable signature of the procedure's shape, ignoring ids and instructions.
    private function shapeOf(Collection $rows): string
    {
        return $rows
            ->sortBy(fn (array $row) => sprintf(
                '%d-%03d',
                array_search($row['phase'], self::PHASES, true),
                $row['correct_position']
            ))
            ->map(fn (array $row) => implode('|', [
                $row['phase'],
                $row['component_key'],
                $row['action'],
                $row['target_zone'],
            ]))
            ->implode(';');
    }
}
