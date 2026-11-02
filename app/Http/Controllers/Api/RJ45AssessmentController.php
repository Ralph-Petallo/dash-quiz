<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Models\Rj45Assessment;
use App\Models\Rj45Attempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class Rj45AssessmentController extends Controller
{
    private const PASSING_SCORE = 75.00;
    private const ASSESSMENT_TYPE = 'rj45_hands_on';
    private const POINTS_STANDARD = 10;
    private const POINTS_PER_PIN = 5;     // 8 pins x 5 = 40
    private const POINTS_CRIMPING = 20;
    private const POINTS_TESTING = 20;
    private const POINTS_PROCEDURE = 10;

    /*
    |--------------------------------------------------------------------------
    | LEARNER
    |--------------------------------------------------------------------------
    */

    /**
     * Load the RJ-45 assessment configuration.
     */
    public function show(int $id)
    {
        $quiz = Quiz::findOrFail($id);

        $rj45Assessment = Rj45Assessment::query()
            ->where('quiz_id', $quiz->id)
            ->where('is_active', true)
            ->with(['pinouts', 'assessment'])
            ->firstOrFail();

        $mainAssessment = $rj45Assessment->assessment;

        abort_unless(
            $mainAssessment,
            422,
            'The RJ-45 practical is not linked to a valid assessment.'
        );

        $standards = $rj45Assessment->pinouts
            ->groupBy('standard')
            ->filter(fn($rows) => $rows->count() === 8)
            ->keys()
            ->sort()
            ->values()
            ->map(fn($standard) => [
                'standard' => $standard,
                'label' => 'T568' . $standard,
            ]);

        $wires = $rj45Assessment->pinouts
            ->unique('wire_code')
            ->map(fn($pin) => [
                'id' => $pin->wire_code,
                'name' => $pin->wire_name,
                'short' => $pin->short_label,
                'color' => $pin->color_hex,
                'striped' => (bool) $pin->striped,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'RJ-45 assessment loaded.',
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'quiz_type' => $mainAssessment->type,
                    'coc_number' => $quiz->coc_number,
                    'difficulty' => $quiz->difficulty,
                    'category' => $quiz->category,
                ],

                'assessment' => [
                    // ID from the main assessments table.
                    'id' => $mainAssessment->id,
                    'assessment_id' => $mainAssessment->id,

                    // ID from the rj45_assessments table.
                    'rj45_assessment_id' => $rj45Assessment->id,

                    'type' => $mainAssessment->type,
                    'app_name' => $rj45Assessment->app_name,
                    'project_title' => $rj45Assessment->project_title,
                    'project_subtitle' => $rj45Assessment->project_subtitle,
                    'title' => $rj45Assessment->title,
                    'description' => $rj45Assessment->description,
                    'coc_label' => $rj45Assessment->coc_label,
                    'default_standard' => $rj45Assessment->default_standard,
                    'test_pin_delay_ms' => $rj45Assessment->test_pin_delay_ms,
                    'tray_order' => $rj45Assessment->tray_order ?? [],
                    'initial_wiring' => $rj45Assessment->initial_wiring
                        ?? array_fill(0, 8, null),
                    'simulation_note' => $rj45Assessment->simulation_note,
                ],

                'wires' => $wires,
                'standards' => $standards,
            ],
        ]);
    }

    /**
     * Test the submitted RJ-45 wiring without saving an attempt.
     */
    public function testWiring(Request $request, int $id)
    {
        $quiz = Quiz::findOrFail($id);

        $rj45Assessment = Rj45Assessment::query()
            ->where('quiz_id', $quiz->id)
            ->where('is_active', true)
            ->with('assessment')
            ->firstOrFail();

        abort_unless(
            $rj45Assessment->assessment,
            422,
            'The RJ-45 practical is not linked to a valid assessment.'
        );

        $validated = $this->validateWiring($request, $rj45Assessment);

        $evaluation = $this->evaluateWiring(
            $rj45Assessment,
            $validated['standard'],
            $validated['wirings']
        );

        return response()->json([
            'success' => true,
            'message' => $evaluation['passed']
                ? 'All RJ-45 pin positions are correct.'
                : 'RJ-45 test completed with incorrect pin positions.',
            'data' => [
                'quiz_id' => $quiz->id,
                'assessment_id' => $rj45Assessment->assessment->id,
                'rj45_assessment_id' => $rj45Assessment->id,
                'quiz_type' => $rj45Assessment->assessment->type,
                'standard' => $validated['standard'],
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $evaluation['score_percent'],
                'passed' => $evaluation['passed'],
                'pins' => $evaluation['pins'],
            ],
        ]);
    }

    /**
     * Save a completed RJ-45 assessment attempt.
     */
    public function storeAttempt(Request $request, int $id)
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated.');

        $quiz = Quiz::findOrFail($id);

        $rj45Assessment = Rj45Assessment::query()
            ->where('quiz_id', $quiz->id)
            ->where('is_active', true)
            ->with('assessment')
            ->firstOrFail();

        $mainAssessment = $rj45Assessment->assessment;

        abort_unless(
            $mainAssessment,
            422,
            'The RJ-45 practical is not linked to a valid assessment.'
        );

        $validated = $this->validateWiring($request, $rj45Assessment, true);

        $evaluation = $this->evaluateWiring(
            $rj45Assessment,
            $validated['standard'],
            $validated['wirings']
        );

        $scoring = $this->scoreAttempt(
            $evaluation,
            $request->boolean('crimped'),
            $request->boolean('tested')
        );

        $saved = DB::transaction(function () use ($user, $quiz, $rj45Assessment, $mainAssessment, $validated, $evaluation, $scoring) {
            $submittedAt = now();

            $attemptNumber = QuizRecord::attemptsFor(
                $user->id,
                $quiz->id,
                $mainAssessment->type
            )
                ->lockForUpdate()
                ->count() + 1;

            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $scoring['total'],
                'elapsed_time' => $this->millisecondsToSeconds($validated['duration_ms'] ?? null),
                'correct_answers' => $evaluation['correct_pins'],
                'total_questions' => 8,
                'percentage' => $scoring['total'],
                'passing_score' => self::PASSING_SCORE,
                'status' => $scoring['passed'] ? 'Passed' : 'Failed',
                'attempt_count' => $attemptNumber,
                'quiz_type' => $mainAssessment->type,
                'category' => $quiz->category ?: $mainAssessment->category ?: 'Network Cabling',
                'completed_at' => $submittedAt,
            ]);

            $attempt = Rj45Attempt::create([
                'rj45_assessment_id' => $rj45Assessment->id,
                'quiz_record_id' => $record->id,
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'standard' => $validated['standard'],
                'wiring' => $validated['wirings'],
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $scoring['total'],
                'passed' => $scoring['passed'],
                'duration_ms' => $validated['duration_ms'] ?? null,
                'started_at' => $validated['started_at'] ?? null,
                'submitted_at' => $submittedAt,
            ]);

            $attempt->pins()->createMany($evaluation['pins']);

            return [
                'record' => $record->fresh('quiz'),
                'attempt' => $attempt->load('pins'),
            ];
        });

        $record = $saved['record'];
        $attempt = $saved['attempt'];

        return response()->json([
            'success' => true,
            'message' => $scoring['passed']
                ? 'RJ-45 assessment passed and recorded.'
                : 'RJ-45 assessment attempt recorded.',
            'data' => [
                'attempt_id' => $attempt->id,
                'record_id' => $record->id,
                'quiz_id' => $quiz->id,
                'assessment_id' => $mainAssessment->id,
                'rj45_assessment_id' => $rj45Assessment->id,
                'quiz_type' => $mainAssessment->type,
                'standard' => $attempt->standard,
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $scoring['total'],
                'score_breakdown' => $scoring['breakdown'],
                'passed' => $scoring['passed'],
                'attempt_count' => (int) $record->attempt_count,
                'elapsed_time' => $record->elapsed_time,
                'status' => $record->status,
                'pins' => $attempt->pins->map(fn($pin) => [
                    'pin_number' => (int) $pin->pin_number,
                    'actual_wire' => $pin->actual_wire,
                    'expected_wire' => $pin->expected_wire,
                    'is_correct' => (bool) $pin->is_correct,
                ])->values(),
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | LEARNER HELPERS
    |--------------------------------------------------------------------------
    */

    private function validateWiring(
        Request $request,
        Rj45Assessment $assessment,
        bool $withTiming = false
    ): array {
        $validCodes = $assessment->pinouts()
            ->pluck('wire_code')
            ->unique()
            ->values()
            ->all();

        $validStandards = $assessment->pinouts()
            ->select('standard')
            ->groupBy('standard')
            ->havingRaw('COUNT(*) = 8')
            ->pluck('standard')
            ->values()
            ->all();

        $rules = [
            'standard' => ['required', 'string', Rule::in($validStandards)],
            'wirings' => ['required', 'array', 'size:8'],
            'wirings.*' => ['required', 'string', 'distinct', Rule::in($validCodes)],
        ];

        if ($withTiming) {
            $rules['started_at'] = ['nullable', 'date'];
            $rules['duration_ms'] = ['nullable', 'integer', 'min:0'];

            // Client-reported actions. The server checks them against the wiring.
            $rules['crimped'] = ['required', 'boolean'];
            $rules['tested'] = ['required', 'boolean'];
        }

        return $request->validate($rules);
    }

    /**
     * Score the attempt using the 100-point CSS Prep RJ-45 rubric.
     */
    private function scoreAttempt(array $evaluation, bool $crimped, bool $tested): array
    {
        $allCorrect = $evaluation['correct_pins'] === 8;
        $procedureDone = $crimped && $tested;

        $breakdown = [
            'standard' => self::POINTS_STANDARD,
            'placement' => $evaluation['correct_pins'] * self::POINTS_PER_PIN,
            'crimping' => ($allCorrect && $crimped) ? self::POINTS_CRIMPING : 0,
            'testing' => $procedureDone ? self::POINTS_TESTING : 0,
            'procedure' => $procedureDone ? self::POINTS_PROCEDURE : 0,
        ];

        $total = array_sum($breakdown);

        return [
            'breakdown' => $breakdown,
            'total' => round($total, 2),
            // Passing requires the score, all eight pins, and the full procedure.
            'passed' => $total >= self::PASSING_SCORE && $allCorrect && $procedureDone,
        ];
    }

    /**
     * Evaluate all eight RJ-45 pin positions.
     */
    private function evaluateWiring(
        Rj45Assessment $assessment,
        string $standard,
        array $wirings
    ): array {
        $expectedRows = $assessment->pinouts()
            ->where('standard', $standard)
            ->orderBy('pin_number')
            ->get();

        abort_unless(
            $expectedRows->count() === 8,
            422,
            'The selected RJ-45 standard is not fully configured.'
        );

        $pins = [];
        $correctPins = 0;

        foreach ($expectedRows as $index => $expected) {
            $actual = $wirings[$index] ?? null;
            $isCorrect = $actual === $expected->wire_code;

            if ($isCorrect) {
                $correctPins++;
            }

            $pins[] = [
                'pin_number' => (int) $expected->pin_number,
                'actual_wire' => $actual,
                'expected_wire' => $expected->wire_code,
                'is_correct' => $isCorrect,
            ];
        }

        return [
            'pins' => $pins,
            'correct_pins' => $correctPins,
            'score_percent' => round(($correctPins / 8) * 100, 2),
            'passed' => $correctPins === 8,
        ];
    }

    private function millisecondsToSeconds(?int $milliseconds): ?int
    {
        if ($milliseconds === null) {
            return null;
        }

        return max(0, (int) round($milliseconds / 1000));
    }

    /*
    |==========================================================================
    | ADMIN: RJ-45 PRACTICAL EDITOR
    |==========================================================================
    | Every method below requires an admin.
    | Wire maps are stored in the pinouts table, one row per pin per standard.
    */

    private const RJ45_STANDARDS = ['A', 'B'];

    // Used only when a new practical has no pinouts yet. Match your seeded wire codes.
    private const RJ45_FALLBACK_WIRES = [
        'white_orange' => ['name' => 'White/Orange', 'short' => 'W/O', 'color' => '#E8802C', 'striped' => true],
        'orange' => ['name' => 'Orange', 'short' => 'O', 'color' => '#E8802C', 'striped' => false],
        'white_green' => ['name' => 'White/Green', 'short' => 'W/G', 'color' => '#1E8449', 'striped' => true],
        'blue' => ['name' => 'Blue', 'short' => 'BL', 'color' => '#2563EB', 'striped' => false],
        'white_blue' => ['name' => 'White/Blue', 'short' => 'W/BL', 'color' => '#2563EB', 'striped' => true],
        'green' => ['name' => 'Green', 'short' => 'G', 'color' => '#1E8449', 'striped' => false],
        'white_brown' => ['name' => 'White/Brown', 'short' => 'W/BR', 'color' => '#8B5A2B', 'striped' => true],
        'brown' => ['name' => 'Brown', 'short' => 'BR', 'color' => '#8B5A2B', 'striped' => false],
    ];

    // Pin 1 to 8, left to right.
    private const RJ45_FALLBACK_MAPS = [
        'A' => ['white_green', 'green', 'white_orange', 'blue', 'white_blue', 'orange', 'white_brown', 'brown'],
        'B' => ['white_orange', 'orange', 'white_green', 'blue', 'white_blue', 'green', 'white_brown', 'brown'],
    ];

    /**
     * POST /api/admin/assessment/rj45/create/{cocId}
     */
    public function store(Request $request, int $cocId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $quiz = Quiz::query()->findOrFail($cocId);

        $assessment = DB::transaction(function () use ($quiz) {
            $assessment = Assessment::query()->firstOrCreate(
                [
                    'coc_id' => $quiz->id,
                    'type' => self::ASSESSMENT_TYPE,
                ],
                [
                    'title' => 'RJ-45 Cable Termination',
                    'category' => $quiz->category ?? 'Networking',
                    'available' => true,
                    // Set this to the learner route for the RJ-45 practical.
                    'path' => '/taking/rj45',
                ]
            );

            $rj45 = Rj45Assessment::query()->firstOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'assessment_id' => $assessment->id,
                ],
                [
                    'is_active' => true,
                    'app_name' => 'CSS Prep',
                    'project_title' => 'RJ-45 Cable Termination',
                    'project_subtitle' => 'Network Cabling',
                    'title' => $assessment->title,
                    'description' => null,
                    'coc_label' => 'COC ' . $quiz->coc_number,
                    'default_standard' => 'B',
                    'test_pin_delay_ms' => 0,
                    'tray_order' => array_keys(self::RJ45_FALLBACK_WIRES),
                    'initial_wiring' => array_fill(0, 8, null),
                    'simulation_note' => null,
                ]
            );

            if ($rj45->wasRecentlyCreated) {
                $catalog = $this->fallbackCatalog();

                foreach (self::RJ45_STANDARDS as $standard) {
                    $this->savePinouts($rj45, $standard, self::RJ45_FALLBACK_MAPS[$standard], $catalog);
                }
            }

            return $assessment;
        });

        return response()->json([
            'status' => 'success',
            'assessment_id' => $assessment->id,
        ], $assessment->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /api/admin/assessment/rj45/edit/{assessmentId}
     *
     * Read-only.
     */
    public function edit(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $assessment = $this->adminAssessment($assessmentId);
        $rj45 = $this->adminRj45($assessment);

        return response()->json([
            'status' => 'success',
            'quiz' => Quiz::query()->find($assessment->coc_id),
            'assessment' => [
                'id' => $assessment->id,
                'title' => $rj45->title ?? $assessment->title,
                'description' => $rj45->description,
            ],
            'config' => [
                'standard_a' => $this->codesFor($rj45, 'A'),
                'standard_b' => $this->codesFor($rj45, 'B'),
                'initial_wiring' => array_values($rj45->initial_wiring ?? array_fill(0, 8, null)),
                'default_standard' => $rj45->default_standard,
                'test_pin_delay_ms' => (int) $rj45->test_pin_delay_ms,
                'simulation_note' => $rj45->simulation_note,
            ],
            'wires' => $this->wireCatalog($rj45),
            'has_attempts' => $this->hasAttempts($rj45),
        ]);
    }

    /**
     * PUT /api/admin/assessment/rj45/update/{assessmentId}
     */
    public function update(Request $request, int $assessmentId): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        $assessment = $this->adminAssessment($assessmentId);
        $rj45 = $this->adminRj45($assessment);

        $catalog = collect($this->wireCatalog($rj45))->keyBy('id')->all();
        $codes = array_keys($catalog);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'simulation_note' => ['nullable', 'string', 'max:500'],
            'default_standard' => ['required', Rule::in(self::RJ45_STANDARDS)],
            'test_pin_delay_ms' => ['required', 'integer', 'min:0', 'max:1000'],

            // Each standard needs 8 pins. A wire can appear only once per standard.
            'standard_a' => ['required', 'array', 'size:8'],
            'standard_a.*' => ['required', 'string', 'distinct', Rule::in($codes)],
            'standard_b' => ['required', 'array', 'size:8'],
            'standard_b.*' => ['required', 'string', 'distinct', Rule::in($codes)],

            // Empty pins are allowed. Repeats are checked below.
            'initial_wiring' => ['required', 'array', 'size:8'],
            'initial_wiring.*' => ['nullable', 'string', Rule::in($codes)],
        ]);

        $initialWiring = array_map(fn($value) => $value ?: null, $validated['initial_wiring']);
        $this->assertNoRepeats($initialWiring, 'initial_wiring');

        // Changing a wire map after attempts exist would make past scores point to the wrong answers.
        if ($this->hasAttempts($rj45)) {
            $unchanged = $this->codesFor($rj45, 'A') === array_values($validated['standard_a'])
                && $this->codesFor($rj45, 'B') === array_values($validated['standard_b']);

            if (!$unchanged) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Learners have already attempted this practical, so the wire maps are locked. You can still edit the text and settings.',
                ], 409);
            }
        }

        try {
            DB::transaction(function () use ($assessment, $rj45, $validated, $initialWiring, $catalog) {
                $assessment->update([
                    'title' => $validated['title'],
                ]);

                $rj45->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'simulation_note' => $validated['simulation_note'] ?? null,
                    'default_standard' => $validated['default_standard'],
                    'test_pin_delay_ms' => $validated['test_pin_delay_ms'],
                    'initial_wiring' => $initialWiring,
                ]);

                $this->savePinouts($rj45, 'A', $validated['standard_a'], $catalog);
                $this->savePinouts($rj45, 'B', $validated['standard_b'], $catalog);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'RJ-45 practical updated successfully.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the RJ-45 practical.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN HELPERS
    |--------------------------------------------------------------------------
    */

    private function adminAssessment(int $assessmentId): Assessment
    {
        return Assessment::query()
            ->where('type', self::ASSESSMENT_TYPE)
            ->findOrFail($assessmentId);
    }

    private function adminRj45(Assessment $assessment): Rj45Assessment
    {
        return Rj45Assessment::query()
            ->where('assessment_id', $assessment->id)
            ->firstOrFail();
    }

    private function hasAttempts(Rj45Assessment $rj45): bool
    {
        return Rj45Attempt::query()
            ->where('rj45_assessment_id', $rj45->id)
            ->exists();
    }

    // Wire list for the editor, built from the wires already stored for this practical.
    private function wireCatalog(Rj45Assessment $rj45): array
    {
        $pinouts = $rj45->pinouts()->get();

        if ($pinouts->isEmpty()) {
            return $this->fallbackCatalogList();
        }

        return $pinouts
            ->unique('wire_code')
            ->map(fn($pin) => [
                'id' => $pin->wire_code,
                'name' => $pin->wire_name,
                'short' => $pin->short_label,
                'color' => $pin->color_hex,
                'striped' => (bool) $pin->striped,
            ])
            ->values()
            ->all();
    }

    private function fallbackCatalogList(): array
    {
        return collect(self::RJ45_FALLBACK_WIRES)
            ->map(fn(array $wire, string $code) => ['id' => $code] + $wire + ['color' => $wire['color']])
            ->values()
            ->all();
    }

    private function fallbackCatalog(): array
    {
        return collect($this->fallbackCatalogList())->keyBy('id')->all();
    }

    // Returns the 8 wire codes for a standard, in pin order. Missing pins are null.
    private function codesFor(Rj45Assessment $rj45, string $standard): array
    {
        $codes = $rj45->pinouts()
            ->where('standard', $standard)
            ->orderBy('pin_number')
            ->pluck('wire_code')
            ->values()
            ->all();

        return array_pad($codes, 8, null);
    }

    private function savePinouts(
        Rj45Assessment $rj45,
        string $standard,
        array $codes,
        array $catalog
    ): void {
        foreach (array_values($codes) as $index => $code) {
            $wire = $catalog[$code] ?? null;

            abort_unless($wire, 422, "Unknown wire: {$code}.");

            $rj45->pinouts()->updateOrCreate(
                [
                    'standard' => $standard,
                    'pin_number' => $index + 1,
                ],
                [
                    'wire_code' => $code,
                    'wire_name' => $wire['name'],
                    'short_label' => $wire['short'],
                    'color_hex' => $wire['color'],
                    'striped' => (bool) $wire['striped'],
                ]
            );
        }
    }

    // Empty pins (null) are ignored. Any other repeated wire is rejected.
    private function assertNoRepeats(array $codes, string $field): void
    {
        $counts = array_count_values(array_filter($codes));
        $repeated = array_keys(array_filter($counts, fn(int $count) => $count > 1));

        if (!$repeated) {
            return;
        }

        $names = array_map(
            fn(string $code) => self::RJ45_FALLBACK_WIRES[$code]['name'] ?? $code,
            $repeated
        );

        throw ValidationException::withMessages([
            $field => ['Each wire can only be used once. Repeated: ' . implode(', ', $names) . '.'],
        ]);
    }
}