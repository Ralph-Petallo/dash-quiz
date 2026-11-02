<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Dasher;
use App\Models\Quiz;
use App\Models\QuizRecord;

class CacheService
{
    public function __construct(private PerformanceService $performance)
    {
    }

    /* 🏆 LEADERBOARD: one assessment, best attempt per learner */
    public static function cacheLeaderboard(int $cocId, string $quizType, int $limit = 100)
    {
        return Cache::remember(
            "dashquiz:leaderboard:{$cocId}:{$quizType}:{$limit}",
            300,
            fn() => app(PerformanceService::class)->leaderboard($cocId, $quizType, $limit)
        );
    }

    /* 🧪 PUBLISHED QUIZZES (unchanged) */
    public static function cachePublishedQuizzes()
    {
        return Cache::remember(
            'dashquiz:quizzes:published',
            600,
            fn() => Quiz::where('is_published', true)->latest()->get()
        );
    }

    /* 📋 USER RECORDS: from quiz_records, not attempts */
    public static function cacheUserRecords(int $userId)
    {
        return Cache::remember(
            "dashquiz:records:user:{$userId}",
            300,
            fn() => QuizRecord::where('user_id', $userId)
                ->with('quiz:id,title,coc_number')
                ->latest('completed_at')
                ->get()
        );
    }

    /* 👤 PROFILE STATS: same numbers as the dashboard */
    public static function cacheProfile(int $userId)
    {
        return Cache::remember(
            "dashquiz:profile:{$userId}",
            600,
            fn() => [
                'user' => Dasher::findOrFail($userId),
                'summary' => app(PerformanceService::class)->summary(userId: $userId),
            ]
        );
    }
}