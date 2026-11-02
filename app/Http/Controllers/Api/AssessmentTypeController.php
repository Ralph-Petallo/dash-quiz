<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\DragDropItem;
use App\Models\ImageIdentificationItem;
use App\Models\InputOutput;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Rj45Assessment;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class AssessmentTypeController extends Controller
{
    // Items a learner receives per attempt. Keep in sync with the controllers.
    private const MULTIPLE_CHOICE_LIMIT = 20;
    private const IMAGE_QUESTION_LIMIT = 10;

    private const PASSING_SCORE = 75;

    /**
     * ============================================================
     * GET ALL COCs
     * ============================================================
     *
     * GET /api/coc
     */
    public function coc(): JsonResponse
    {
        $cocs = Quiz::query()
            ->where('quiz_type', 'coc')
            ->withCount([
                'assessments',
                'assessments as available_assessments_count' => function ($query) {
                    $query->where('available', true);
                },
            ])
            ->orderBy('coc_number')
            ->get()
            ->map(fn(Quiz $coc) => $this->formatCoc($coc))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $cocs,
            'meta' => [
                'system' => 'CSS Prep',
                'qualification' => 'Computer Systems Servicing NC II',
                'coc_count' => $cocs->count(),
                'assessment_count' => $cocs->sum('assessment_count'),
                'available_assessments' => $cocs->sum('available_assessment_count'),
            ],
        ]);
    }

    /**
     * ============================================================
     * GET SELECTED COC + ASSESSMENTS
     * ============================================================
     *
     * GET /api/selected-assessment/{id}
     */
    public function getAssessmentTypes(int $id): JsonResponse
    {
        $coc = Quiz::query()
            ->where('quiz_type', 'coc')
            ->whereKey($id)
            ->with([
                'assessments' => function ($query) {
                    $query->orderByDesc('available')->orderBy('id');
                },
            ])
            ->withCount([
                'assessments',
                'assessments as available_assessments_count' => function ($query) {
                    $query->where('available', true);
                },
            ])
            ->firstOrFail();

        $assessments = $coc->assessments
            ->map(fn(Assessment $assessment) => $this->formatAssessment($assessment))
            ->values();

        return response()->json([
            'status' => 'success',
            'coc' => $this->formatCoc($coc),
            'assessments' => $assessments,
            'meta' => [
                'system' => 'CSS Prep',
                'qualification' => 'Computer Systems Servicing NC II',
                'coc_label' => 'COC ' . $coc->coc_number,
                'total_assessments' => (int) $coc->assessments_count,
                'available_assessments' => (int) $coc->available_assessments_count,
            ],
        ]);
    }

    /**
     * ============================================================
     * FORMAT COC
     * ============================================================
     */
    private function formatCoc(Quiz $coc): array
    {
        return [
            'id' => (int) $coc->id,
            'coc_number' => (int) $coc->coc_number,
            'title' => $coc->title,
            'description' => $coc->description,
            'category' => $coc->category,
            'difficulty' => $coc->difficulty,
            'quiz_type' => $coc->quiz_type,
            'assessment_count' => (int) ($coc->assessments_count ?? 0),
            'available_assessment_count' => (int) ($coc->available_assessments_count ?? 0),
        ];
    }

    /**
     * ============================================================
     * FORMAT ASSESSMENT
     * ============================================================
     */
    private function formatAssessment(Assessment $assessment): array
    {
        return [
            'id' => (int) $assessment->id,
            'coc_id' => (int) $assessment->coc_id,
            'type' => $assessment->type,
            'title' => $assessment->title,
            'icon' => $assessment->icon,
            'description' => $assessment->description,
            'category' => $assessment->category,
            'difficulty' => $assessment->difficulty,
            'meta' => $this->buildLearnerMeta($assessment),
            'path' => $assessment->path,
            'available' => (bool) $assessment->available,
        ];
    }

    /**
     * ============================================================
     * LEARNER META
     * ============================================================
     *
     * Stored meta is the fallback. Live values computed from the database win.
     * Internal keys (display text, setup flags) are dropped so the card only
     * receives what it should show.
     */
    private function buildLearnerMeta(Assessment $assessment): array
    {
        $stored = $this->normalizeMeta($assessment->meta);
        $live = $this->liveMeta($assessment);

        $merged = array_merge($stored, $live);

        return array_diff_key($merged, array_flip([
            'hidden_procedure',
            'visual',
            'mode',
            'items',
        ]));
    }

    /**
     * Accept meta as an array, a JSON string, a plain list, or old plain text.
     * A plain list such as ['18 Images'] becomes ['display' => ['18 Images']].
     */
    private function normalizeMeta(mixed $meta): array
    {
        if (is_string($meta)) {
            $text = trim($meta);

            if ($text === '') {
                return [];
            }

            $decoded = json_decode($text, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $meta = $decoded;
            } else {
                return ['assessment_format' => $text];
            }
        }

        if (!is_array($meta)) {
            return [];
        }

        if (array_is_list($meta)) {
            return [
                'display' => array_values(array_filter(
                    array_map(fn($value) => is_scalar($value) ? trim((string) $value) : null, $meta),
                    fn($value) => $value !== null && $value !== ''
                )),
            ];
        }

        return $meta;
    }

    /**
     * Counts and facts computed from the database, per assessment type.
     */
    private function liveMeta(Assessment $assessment): array
    {
        return match ($assessment->type) {
            'multiple_choice' => $this->multipleChoiceMeta($assessment),
            'image_identification' => $this->imageMeta($assessment),
            'drag_drop' => $this->inputOutputMeta(),
            'hardware_procedure' => $this->procedureMeta($assessment),
            'rj45_hands_on' => $this->rj45Meta($assessment),
            default => [],
        };
    }

    private function multipleChoiceMeta(Assessment $assessment): array
    {
        $bank = Question::query()
            ->where('quiz_id', $assessment->coc_id)
            ->where(function ($query) {
                $query->whereNull('question_type')
                    ->orWhere('question_type', '!=', 'drag_drop');
            })
            ->whereHas('options')
            ->count();

        return [
            'assessment_format' => 'Multiple choice',
            'per_attempt' => min(self::MULTIPLE_CHOICE_LIMIT, $bank),
            'bank_size' => $bank,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function imageMeta(Assessment $assessment): array
    {
        $bank = ImageIdentificationItem::query()
            ->where('assessment_id', $assessment->id)
            ->whereNotNull('image_path')
            ->whereNotNull('description')
            ->count();

        return [
            'assessment_format' => 'Image identification',
            'per_attempt' => min(self::IMAGE_QUESTION_LIMIT, $bank),
            'bank_size' => $bank,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function inputOutputMeta(): array
    {
        $devices = InputOutput::query()
            ->where('is_active', true)
            ->count();

        return [
            'assessment_format' => 'Classify devices',
            'per_attempt' => $devices,
            'max_score' => $devices * 10,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function procedureMeta(Assessment $assessment): array
    {
        $questionIds = Question::query()
            ->where('quiz_id', $assessment->coc_id)
            ->pluck('id');

        $steps = DragDropItem::query()
            ->whereIn('question_id', $questionIds)
            ->where('active', true)
            ->where('required', true)
            ->selectRaw('phase, COUNT(*) AS total')
            ->groupBy('phase')
            ->pluck('total', 'phase');

        $disassembly = (int) ($steps['disassemble'] ?? 0);
        $assembly = (int) ($steps['assemble'] ?? 0);

        return [
            'assessment_format' => 'Guided practical',
            'disassembly_actions' => $disassembly,
            'assembly_actions' => $assembly,
            'total_actions' => $disassembly + $assembly,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function rj45Meta(Assessment $assessment): array
    {
        $practical = Rj45Assessment::query()
            ->where('quiz_id', $assessment->coc_id)
            ->where('is_active', true)
            ->with('pinouts')
            ->first();

        $standards = $practical
            ? $practical->pinouts
                ->pluck('standard')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all()
            : [];

        return array_filter([
            'assessment_format' => 'Guided practical',
            'pins' => 8,
            'standards' => $standards,
            'passing_score' => self::PASSING_SCORE,
        ], fn($value) => $value !== [] && $value !== null);
    }

}