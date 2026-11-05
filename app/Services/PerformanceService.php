<?php

namespace App\Services;

use App\Models\QuizRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PerformanceService
{
    public const DEFAULT_PASSING_SCORE = 75.00;

    /**
     * Completed attempts, optionally narrowed by user, COC, or assessment type.
     */
    public function attempts(
        ?int $userId = null,
        ?int $cocId = null,
        ?string $quizType = null
    ): Builder {
        return QuizRecord::query()
            ->whereNotNull('completed_at')
            ->when($userId, fn(Builder $query) => $query->where('user_id', $userId))
            ->when($cocId, fn(Builder $query) => $query->where('quiz_id', $cocId))
            ->when($quizType, fn(Builder $query) => $query->where('quiz_type', $quizType));
    }

    /**
     * The percentage used throughout the application.
     */
    public function percentage(QuizRecord $record): float
    {
        return round((float) $record->percentage, 2);
    }

    /**
     * The pass rule used throughout the application.
     */
    public function passed(QuizRecord $record): bool
    {
        $threshold = (float) (
            $record->passing_score ?? self::DEFAULT_PASSING_SCORE
        );

        return $this->percentage($record) >= $threshold;
    }

    /**
     * Summary for a learner, a COC, an assessment type, or everyone.
     */
    public function summary(
        ?int $userId = null,
        ?int $cocId = null,
        ?string $quizType = null
    ): array {
        $records = $this->attempts($userId, $cocId, $quizType)->get();

        $percentages = $records->map(
            fn(QuizRecord $record) => $this->percentage($record)
        );

        $passed = $records
            ->filter(fn(QuizRecord $record) => $this->passed($record))
            ->count();

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
     * Leaderboard for one assessment.
     *
     * Each learner appears once, using their best attempt.
     */
    public function leaderboard(
        int $cocId,
        string $quizType,
        int $limit = 100,
        ?Carbon $since = null
    ): Collection {
        $limit = max(1, min($limit, 200));

        return $this->attempts(null, $cocId, $quizType)
            ->when(
                $since,
                fn(Builder $query) => $query->where('completed_at', '>=', $since)
            )
            ->with([
                'user:id,first_name,last_name,profile_photo',
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