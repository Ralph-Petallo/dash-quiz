<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Dasher;
use App\Models\QuizRecord;

class UserApiController extends Controller
{
    // Get top users based on quiz scores
    public function leaderboard()
    {
        // Fetch quiz records with related user and quiz
        // whereHas('user') ensures the record still has a valid user
        $leaders = QuizRecord::with(['user', 'quiz'])
            ->whereHas('user')
            ->orderByDesc('score') // highest scores first
            ->limit(10) // show only top 10 users
            ->get()
            ->map(function ($record) {
                return [
                    'name' => "{$record->user->first_name} {$record->user->last_name}",
                    'profile_photo' => $record->user->profile_photo,
                    'score' => $record->score,
                    'quiz_title' => $record->quiz->title,
                    'user_id' => $record->user_id
                ];
            });
        return response()->json([
            'status' => 'success',
            'data' => $leaders
        ]);
    }
    // Get list of available quizzes
    public function quizzes()
    {
        // Retrieve quiz list with only necessary fields
        // Reduces response size and improves performance
        $quizzes = Quiz::all(['id', 'title', 'description']);
        return response()->json([
            'status' => 'success',
            'data' => $quizzes
        ]);
    }
    public function profile()
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        try {
            $user->active_status = 1;
            $user->last_activity = now();
            $user->save();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'results' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'full_name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'profile_photo' => $user->profile_photo ?? 'default.png',
                'created_at' => $user->created_at?->format('F j, Y'),
                'quizzes_taken' => QuizRecord::where('user_id', $user->id)->count(),
                'active_status' => $user->active_status,
            ]
        ]);
    }

    // Get quiz history of the logged-in user
    public function records()
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user(); // authenticated user object

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $records = QuizRecord::where('user_id', $user->id)
            ->with('quiz')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($record) {
                return [
                    'quiz_id' => $record->quiz_id,
                    'score' => $record->score,
                    'quiz_title' => $record->quiz->title,
                    'quiz_description' => $record->quiz->description,
                    'created_at' => $record->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'results' => $records,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
            ]
        ]);
    }
}
