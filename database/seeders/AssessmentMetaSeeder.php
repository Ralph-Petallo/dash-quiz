<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\DragDropItem;
use App\Models\ImageIdentificationItem;
use App\Models\InputOutput;
use App\Models\Question;
use App\Models\Rj45Assessment;
use Illuminate\Database\Seeder;

class AssessmentMetaSeeder extends Seeder
{
    private const MULTIPLE_CHOICE_LIMIT = 20;
    private const IMAGE_QUESTION_LIMIT = 10;
    private const PASSING_SCORE = 75;

    public function run(): void
    {
        Assessment::query()->each(function (Assessment $assessment) {
            $assessment->meta = $this->metaFor($assessment);
            $assessment->save();
        });
    }

    private function metaFor(Assessment $assessment): array
    {
        return match ($assessment->type) {
            'multiple_choice' => $this->multipleChoiceMeta($assessment),
            'image_identification' => $this->imageMeta($assessment),
            'drag_drop' => $this->inputOutputMeta(),
            'hardware_procedure' => $this->procedureMeta($assessment),
            'rj45_hands_on' => $this->rj45Meta($assessment),
            default => $assessment->meta ?? [],
        };
    }

    private function multipleChoiceMeta(Assessment $assessment): array
    {
        $bankSize = Question::query()
            ->where('quiz_id', $assessment->coc_id)
            ->where(function ($query) {
                $query->whereNull('question_type')
                    ->orWhere('question_type', '!=', 'drag_drop');
            })
            ->whereHas('options')
            ->count();

        return [
            'assessment_format' => 'Multiple choice',
            'per_attempt' => min(self::MULTIPLE_CHOICE_LIMIT, $bankSize),
            'bank_size' => $bankSize,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function imageMeta(Assessment $assessment): array
    {
        $bankSize = ImageIdentificationItem::query()
            ->where('assessment_id', $assessment->id)
            ->whereNotNull('image_path')
            ->whereNotNull('description')
            ->count();

        return [
            'assessment_format' => 'Image identification',
            'per_attempt' => min(self::IMAGE_QUESTION_LIMIT, $bankSize),
            'bank_size' => $bankSize,
            'passing_score' => self::PASSING_SCORE,
        ];
    }

    private function inputOutputMeta(): array
    {
        $deviceCount = InputOutput::query()
            ->where('is_active', true)
            ->count();

        return [
            'assessment_format' => 'Classify devices',
            'per_attempt' => $deviceCount,
            'max_score' => $deviceCount * 10,
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