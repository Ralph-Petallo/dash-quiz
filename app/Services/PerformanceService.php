<?php

namespace App\Services;

use App\Models\QuizRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PerformanceService
{
    public const DEFAULT_PASSING_SCORE = 75.00;

    /**
     * Completed attempts, optionally narrowed by user, COC, or assessment type.
     */
    public function attempts(?int $userId = null, ?int $cocId = null, ?string $quizType = null): Builder
    {
        return QuizRecord::query()
            ->whereNotNull('completed_at')
            ->when($userId, fn(Builder $q) => $q->where('user_id', $userId))
            ->when($cocId, fn(Builder $q) => $q->where('quiz_id', $cocId))
            ->when($quizType, fn(Builder $q) => $q->where('quiz_type', $quizType));
    }

    /**
     * The percentage used everywhere.
     */
    public function percentage(QuizRecord $record): float
    {
        return round((float) $record->percentage, 2);
    }

    /**
     * The pass rule used everywhere. Uses the passing score stored on the attempt.
     */
    public function passed(QuizRecord $record): bool
    {
        $threshold = (float) ($record->passing_score ?? self::DEFAULT_PASSING_SCORE);

        return $this->percentage($record) >= $threshold;
    }

    /**
     * Summary for a learner, a COC, an assessment type, or everyone.
     */
    public function summary(?int $userId = null, ?int $cocId = null, ?string $quizType = null): array
    {
        $records = $this->attempts($userId, $cocId, $quizType)->get();

        $percentages = $records->map(fn(QuizRecord $r) => $this->percentage($r));
        $passed = $records->filter(fn(QuizRecord $r) => $this->passed($r))->count();
        $total = $records->count();

        return [
            'average_score' => $total ? round($percentages->avg(), 2) : 0.0,
            'best_score' => $total ? round($percentages->max(), 2) : 0.0,
            'passed' => $passed,
            'failed' => $total - $passed,
            'total_attempts' => $total,
        ];
    }

    /**
     * Leaderboard for one assessment: each learner's best attempt, ranked.
     */
    public function leaderboard(int $cocId, string $quizType, int $limit = 100): Collection
    {
        return $this->attempts(null, $cocId, $quizType)
            ->with([
                'user:id,first_name,last_name,profile_photo',
                'quiz:id,title,coc_number',
            ])
            ->orderByDesc('percentage')
            ->orderByDesc('score')
            ->orderByRaw('CASE WHEN elapsed_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('elapsed_time')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->limit(2000)
            ->get()
            ->unique('user_id')
            ->take($limit)
            ->values();
    }
}