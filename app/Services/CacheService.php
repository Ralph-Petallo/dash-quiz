<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class CacheService
{
    /* =========================
       🏆 LEADERBOARD
    ========================== */
    public static function cacheLeaderboard(int $quizId)
    {
        return Cache::remember(
            "dashquiz:leaderboard:$quizId",
            300,
            function () use ($quizId) {
                return QuizAttempt::where('quiz_id', $quizId)
                    ->with('user:id,name')
                    ->orderByDesc('score')
                    ->take(10)
                    ->get();
            }
        );
    }

    /* =========================
       🧪 PUBLISHED QUIZZES
    ========================== */
    public static function cachePublishedQuizzes()
    {
        return Cache::remember(
            "dashquiz:quizzes:published",
            600,
            function () {
                return Quiz::where('is_published', true)
                    ->latest()
                    ->get();
            }
        );
    }

    //USER RECORDS (attempt history)
    public static function cacheUserRecords(int $userId)
    {
        return Cache::remember(
            "dashquiz:records:user:$userId",
            300,
            function () use ($userId) {
                return QuizAttempt::where('user_id', $userId)
                    ->with('quiz:id,title')
                    ->latest()
                    ->get();
            }
        );
    }

    /* =========================
       👤 PROFILE PAGE DATA
    ========================== */
    public static function cacheProfile(int $userId)
    {
        return Cache::remember(
            "dashquiz:profile:$userId",
            600,
            function () use ($userId) {
                $user = User::findOrFail($userId);

                return [
                    'user' => $user,
                    'total_attempts' => QuizAttempt::where('user_id', $userId)->count(),
                    'avg_score' => QuizAttempt::where('user_id', $userId)->avg('score'),
                    'best_score' => QuizAttempt::where('user_id', $userId)->max('score'),
                ];
            }
        );
    }
}
