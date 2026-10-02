<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Models\Rj45Assessment;
use App\Models\Rj45Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RJ45AssessmentController extends Controller
{
    public function show(Quiz $quiz)
    {
        $assessment = $this->assessmentForQuiz($quiz, true);

        $standards = $assessment->pinouts
            ->groupBy('standard')
            ->filter(fn($rows) => $rows->count() === 8)
            ->keys()
            ->sort()
            ->values()
            ->map(fn($standard) => [
                'standard' => $standard,
                'label' => 'T568' . $standard,
            ]);

        $wires = $assessment->pinouts
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
                    'quiz_type' => $quiz->quiz_type,
                    'coc_number' => $quiz->coc_number,
                    'difficulty' => $quiz->difficulty,
                    'category' => $quiz->category,
                ],
                'assessment' => [
                    'id' => $assessment->id,
                    'app_name' => $assessment->app_name,
                    'project_title' => $assessment->project_title,
                    'project_subtitle' => $assessment->project_subtitle,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'coc_label' => $assessment->coc_label,
                    'default_standard' => $assessment->default_standard,
                    'test_pin_delay_ms' => $assessment->test_pin_delay_ms,
                    'tray_order' => $assessment->tray_order ?? [],
                    'initial_wiring' => $assessment->initial_wiring ?? array_fill(0, 8, null),
                    'simulation_note' => $assessment->simulation_note,
                ],
                'wires' => $wires,
                'standards' => $standards,
            ],
        ]);
    }

    public function testWiring(Request $request, Quiz $quiz)
    {
        $assessment = $this->assessmentForQuiz($quiz);
        $validated = $this->validateWiring($request, $assessment);
        $evaluation = $this->evaluateWiring($assessment, $validated['standard'], $validated['wirings']);

        return response()->json([
            'success' => true,
            'message' => $evaluation['passed']
                ? 'All RJ-45 pin positions are correct.'
                : 'RJ-45 test completed with incorrect pin positions.',
            'data' => [
                'quiz_id' => $quiz->id,
                'assessment_id' => $assessment->id,
                'standard' => $validated['standard'],
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $evaluation['score_percent'],
                'passed' => $evaluation['passed'],
                'pins' => $evaluation['pins'],
            ],
        ]);
    }

    public function storeAttempt(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');

        $assessment = $this->assessmentForQuiz($quiz);
        $validated = $this->validateWiring($request, $assessment, true);
        $evaluation = $this->evaluateWiring(
            $assessment,
            $validated['standard'],
            $validated['wirings']
        );

        $saved = DB::transaction(function () use ($user, $quiz, $assessment, $validated, $evaluation) {
            $submittedAt = now();
            $elapsedSeconds = $this->millisecondsToSeconds($validated['duration_ms'] ?? null);

            $attemptNumber = QuizRecord::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->lockForUpdate()
                ->count() + 1;

            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $evaluation['correct_pins'],
                'elapsed_time' => $elapsedSeconds,
                'correct_answers' => $evaluation['correct_pins'],
                'total_questions' => 8,
                'percentage' => $evaluation['score_percent'],
                'passing_score' => 100,
                'status' => $evaluation['passed'] ? 'passed' : 'failed',
                'attempt_count' => $attemptNumber,
                'quiz_type' => $quiz->quiz_type ?: 'rj45_hands_on',
                'category' => $quiz->category ?: 'Network Cabling',
                'completed_at' => $submittedAt,
            ]);

            $attempt = Rj45Attempt::create([
                'rj45_assessment_id' => $assessment->id,
                'quiz_record_id' => $record->id,
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'standard' => $validated['standard'],
                'wiring' => $validated['wirings'],
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $evaluation['score_percent'],
                'passed' => $evaluation['passed'],
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
            'message' => $evaluation['passed']
                ? 'RJ-45 assessment passed and recorded.'
                : 'RJ-45 assessment attempt recorded.',
            'data' => [
                'attempt_id' => $attempt->id,
                'record_id' => $record->id,
                'quiz_id' => $quiz->id,
                'assessment_id' => $assessment->id,
                'standard' => $attempt->standard,
                'correct_pins' => $evaluation['correct_pins'],
                'score_percent' => $evaluation['score_percent'],
                'passed' => $evaluation['passed'],
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

    private function assessmentForQuiz(Quiz $quiz, bool $withPinouts = false): Rj45Assessment
    {
        $query = Rj45Assessment::query()
            ->where('quiz_id', $quiz->id)
            ->where('is_active', true);

        if ($withPinouts) {
            $query->with('pinouts');
        }

        return $query->firstOrFail();
    }

    private function validateWiring(Request $request, Rj45Assessment $assessment, bool $withTiming = false): array
    {
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
        }

        return $request->validate($rules);
    }

    private function evaluateWiring(Rj45Assessment $assessment, string $standard, array $wirings): array
    {
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
}
