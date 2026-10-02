<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dasher;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserApiController extends Controller
{
    /**
     * ────────────────────────────────────────────────────
     * USER PROFILE ENDPOINTS
     * ────────────────────────────────────────────────────
     */

    /**
     * Get authenticated user profile with statistics
     *
     * @return JsonResponse
     */
    /**
     * Get authenticated user profile with statistics
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::guard('dasher')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        try {
            // Update user activity
            $user->update([
                'active_status' => 1,
                'last_activity' => now(),
            ]);

            if ($user->last_activity < now()->subMinutes(5)) {
                $user->update(['last_activity' => now()]);
            }

        } catch (\Exception $e) {
            \Log::error('Profile update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user activity.'
            ], 500);
        }

        // Get statistics (consider caching if queries are expensive)
        $userId = $user->id;
        $quizzesTaken = QuizRecord::where('user_id', $userId)->count();
        $averageScore = QuizRecord::where('user_id', $userId)->avg('percentage') ?? 0;
        $totalAttempts = QuizRecord::where('user_id', $userId)->sum('attempt_count') ?? 0;

        return response()->json([
            'status' => 'success',
            'results' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim("{$user->first_name} {$user->last_name}"),
                'role' => $user->role,
                'email' => $user->email,
                'profile_photo' => $user->profile_photo ?? 'default.png',
                'created_at' => $user->created_at?->format('F j, Y'),
                'active_status' => $user->active_status,
                'statistics' => [
                    'quizzes_taken' => $quizzesTaken,
                    'average_score' => round($averageScore, 2),
                    'total_attempts' => $totalAttempts,
                ]
            ]
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * QUIZ ENDPOINTS
     * ────────────────────────────────────────────────────
     */

    /**
     * Get list of available quizzes with metadata
     *
     * @return JsonResponse
     */
    public function quizzes(): JsonResponse
    {
        $quizzes = Quiz::with('questions')
            ->orderBy('title')
            ->get()
            ->map(function ($quiz) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'total_questions' => $quiz->questions->count(),
                    'quiz_type' => $quiz->quiz_type,
                    'coc_number' => $quiz->coc_number,
                    'difficulty' => $quiz->difficulty,
                    'category' => $quiz->category,
                    'passing_score' => $quiz->passing_score ?? 70,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $quizzes
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * RECORD ENDPOINTS - LISTING & RETRIEVAL
     * ────────────────────────────────────────────────────
     */

    /**
     * Get all quiz records for authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    // Get assessment history of the logged-in user
public function records()
{
    /** @var Dasher|null $user */
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated.'
        ], 401);
    }

    $records = QuizRecord::with('quiz')
        ->where('user_id', $user->id)
        ->latest('completed_at')
        ->latest('created_at')
        ->get()
        ->map(function ($record) {

            $percentage = $record->percentage !== null
                ? (float) $record->percentage
                : (
                    $record->total_questions > 0
                        ? round(
                            ($record->correct_answers /
                                $record->total_questions) * 100,
                            2
                        )
                        : 0
                );

            $passingScore = $record->passing_score !== null
                ? (float) $record->passing_score
                : 75;

            return [

                // -----------------------------------------
                // IDENTIFICATION
                // -----------------------------------------

                'id' => $record->id,
                'user_id' => $record->user_id,
                'coc_id' => $record->quiz_id,
                // -----------------------------------------
                // ASSESSMENT
                // -----------------------------------------

                'coc_title' =>
                    $record->quiz->title
                    ?? 'Untitled Assessment',

                'quiz_description' =>
                    $record->quiz->description
                    ?? null,


                // -----------------------------------------
                // SCORE
                // -----------------------------------------

                'score' =>
                    (int) ($record->score ?? $record->correct_answers ?? 0),

                'correct_answers' =>
                    (int) ($record->correct_answers ?? 0),

                'total_questions' =>
                    (int) ($record->total_questions ?? 0),

                'percentage' =>
                    $percentage,


                // -----------------------------------------
                // PASSING
                // -----------------------------------------

                'passing_score' =>
                    $passingScore,

                'status' =>
                    $record->status
                    ?? (
                        $percentage >= $passingScore
                            ? 'Passed'
                            : 'Failed'
                    ),


                // -----------------------------------------
                // ASSESSMENT INFO
                // -----------------------------------------

                'quiz_type' =>
                    $record->quiz_type
                    ?? 'multiple_choice',

                'category' =>
                    $record->category
                    ?? null,


                // -----------------------------------------
                // ATTEMPT
                // -----------------------------------------

                'attempt_count' =>
                    (int) ($record->attempt_count ?? 1),

                'attempt' =>
                    (int) ($record->attempt_count ?? 1),


                // -----------------------------------------
                // TIME
                // -----------------------------------------

                'elapsed_time' =>
                    $record->elapsed_time,


                // -----------------------------------------
                // DATES
                // -----------------------------------------

                'completed_at' =>
                    $record->completed_at,

                'created_at' =>
                    $record->created_at,

                'updated_at' =>
                    $record->updated_at,

                'formatted_date' =>
                    ($record->completed_at ?? $record->created_at)
                        ? ($record->completed_at ?? $record->created_at)
                            ->format('Y-m-d H:i:s')
                        : null,
            ];
        });

    return response()->json([
        'status' => 'success',

        'results' => $records,

        'user' => [
            'id' => $user->id,

            'full_name' => trim(
                "{$user->first_name} {$user->last_name}"
            ),

            'email' => $user->email,
        ]
    ]);
}

    /**
     * Get paginated records for user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordsPaginated(Request $request): JsonResponse
    {
        $user = auth('dasher')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])
            ->where('user_id', $user->id);

        // Apply filters
        $this->applyRecordFilters($query, $request);

        $paginated = $query->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'count' => $paginated->count(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages' => $paginated->lastPage(),
                'has_more' => $paginated->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get single record by ID
     *
     * @param int $recordId
     * @return JsonResponse
     */
    public function getRecord(int $recordId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $record = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])
            ->where('id', $recordId)
            ->where('user_id', $user->id)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatRecord($record, true)
        ]);
    }

    /**
     * Get recent quiz records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentRecords(Request $request): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $limit = $request->get('limit', 5);

        $records = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return $this->formatRecord($record);
            });

        return response()->json([
            'status' => 'success',
            'data' => $records
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * RECORD ENDPOINTS - ANALYTICS & STATISTICS
     * ────────────────────────────────────────────────────
     */

    /**
     * Get comprehensive record statistics for user
     *
     * @return JsonResponse
     */
    public function recordsStatistics(): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | USER / COC
        |--------------------------------------------------------------------------
        */

        $cocNumber = $user->coc_number ?? null;


        /*
        |--------------------------------------------------------------------------
        | QUIZ RECORDS
        |--------------------------------------------------------------------------
        */

        $records = QuizRecord::with('quiz')
            ->where('user_id', $user->id)
            ->get();

        $totalQuizRecords = $records->count();

        $passedQuizRecords = $records->filter(function ($record) {
            return $record->percentage >= 70;
        })->count();

        $failedQuizRecords = $totalQuizRecords - $passedQuizRecords;

        $averageQuizScore = $records->avg('percentage') ?? 0;
        $personalBest = $records->max('percentage') ?? 0;
        $personalWorst = $records->min('percentage') ?? 0;

        /*
        |--------------------------------------------------------------------------
        | QUIZ BY CATEGORY
        |--------------------------------------------------------------------------
        */

        $recordsByCategory = $records
            ->groupBy(function ($record) {
                return $record->quiz->category ?? 'Uncategorized';
            })
            ->map(function ($categoryRecords) {

                $total = $categoryRecords->count();

                $passed = $categoryRecords->filter(function ($record) {
                    return $record->percentage >= 70;
                })->count();

                return [
                    'total' => $total,
                    'average_score' => round(
                        $categoryRecords->avg('percentage'),
                        2
                    ),
                    'passed' => $passed,
                    'failed' => $total - $passed,
                    'pass_rate' => $total > 0
                        ? round(($passed / $total) * 100, 2)
                        : 0,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | QUIZ BY TYPE
        |--------------------------------------------------------------------------
        */

        $recordsByType = $records
            ->groupBy(function ($record) {
                return $record->quiz->quiz_type ?? 'Unknown';
            })
            ->map(function ($typeRecords) {

                $total = $typeRecords->count();

                $passed = $typeRecords->filter(function ($record) {
                    return $record->percentage >= 70;
                })->count();

                return [
                    'total' => $total,
                    'average_score' => round(
                        $typeRecords->avg('percentage'),
                        2
                    ),
                    'passed' => $passed,
                    'failed' => $total - $passed,
                    'pass_rate' => $total > 0
                        ? round(($passed / $total) * 100, 2)
                        : 0,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | VIRTUAL HANDS-ON RECORDS
        |--------------------------------------------------------------------------
        |
        | Change VirtualHandsOnRecord to whatever model you actually use.
        |
        */

        $handsOnRecords = Assessment::where(
            'user_id',
            $user->id
        )->get();

        $totalHandsOn = $handsOnRecords->count();

        $passedHandsOn = $handsOnRecords->filter(function ($record) {
            return $record->percentage >= 70;
        })->count();

        $failedHandsOn = $totalHandsOn - $passedHandsOn;

        $averageHandsOnScore = $handsOnRecords->avg('percentage') ?? 0;


        /*
        |--------------------------------------------------------------------------
        | HANDS-ON BY ASSESSMENT
        |--------------------------------------------------------------------------
        */

        $handsOnByAssessment = $handsOnRecords
            ->groupBy('assessment_type')
            ->map(function ($assessmentRecords) {

                $total = $assessmentRecords->count();

                $passed = $assessmentRecords->filter(function ($record) {
                    return $record->percentage >= 70;
                })->count();

                return [
                    'total' => $total,
                    'average_score' => round(
                        $assessmentRecords->avg('percentage'),
                        2
                    ),
                    'passed' => $passed,
                    'failed' => $total - $passed,
                    'pass_rate' => $total > 0
                        ? round(($passed / $total) * 100, 2)
                        : 0,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | COMBINED ASSESSMENT TOTALS
        |--------------------------------------------------------------------------
        */

        $totalAssessments = $totalQuizRecords + $totalHandsOn;
        $totalPassed = $passedQuizRecords + $passedHandsOn;
        $totalFailed = $failedQuizRecords + $failedHandsOn;


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | USER INFORMATION
                |--------------------------------------------------------------------------
                */

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'coc_number' => $cocNumber,
                ],


                /*
                |--------------------------------------------------------------------------
                | OVERALL
                |--------------------------------------------------------------------------
                */

                'overview' => [
                    'total_assessments' => $totalAssessments,
                    'passed' => $totalPassed,
                    'failed' => $totalFailed,

                    'pass_rate' => $totalAssessments > 0
                        ? round(
                            ($totalPassed / $totalAssessments) * 100,
                            2
                        )
                        : 0,
                ],


                /*
                |--------------------------------------------------------------------------
                | QUIZ
                |--------------------------------------------------------------------------
                */

                'quiz' => [
                    'total' => $totalQuizRecords,
                    'passed' => $passedQuizRecords,
                    'failed' => $failedQuizRecords,

                    'pass_rate' => $totalQuizRecords > 0
                        ? round(
                            ($passedQuizRecords / $totalQuizRecords) * 100,
                            2
                        )
                        : 0,

                    'average' => round($averageQuizScore, 2),
                    'best' => $personalBest,
                    'worst' => $personalWorst,

                    'by_category' => $recordsByCategory,
                    'by_type' => $recordsByType,
                ],


                /*
                |--------------------------------------------------------------------------
                | VIRTUAL HANDS-ON
                |--------------------------------------------------------------------------
                */

                'hands_on' => [
                    'total' => $totalHandsOn,
                    'passed' => $passedHandsOn,
                    'failed' => $failedHandsOn,

                    'pass_rate' => $totalHandsOn > 0
                        ? round(
                            ($passedHandsOn / $totalHandsOn) * 100,
                            2
                        )
                        : 0,

                    'average' => round($averageHandsOnScore, 2),

                    'by_assessment' => $handsOnByAssessment,
                ],
            ],
        ]);
    }


    /**
     * Get records filtered by category
     *
     * @param string $category
     * @return JsonResponse
     */
    public function recordsByCategory(string $category): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $records = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])
            ->where('user_id', $user->id)
            ->whereHas('quiz', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->latest()
            ->get()
            ->map(function ($record) {
                return $this->formatRecord($record);
            });

        return response()->json([
            'status' => 'success',
            'category' => $category,
            'data' => $records
        ]);
    }

    /**
     * Get records for a specific quiz (all attempts)
     *
     * @param int $quizId
     * @return JsonResponse
     */
    public function recordsByQuiz(int $quizId): JsonResponse
    {
        $user = auth('dasher')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $records = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->latest()
            ->get()
            ->map(function ($record) {
                return $this->formatRecord($record);
            });

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No records found for this quiz.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'quiz_id' => $quizId,
            'total_attempts' => $records->count(),
            'data' => $records
        ]);
    }

    /**
     * Get comparison between attempts on same quiz
     *
     * @param int $quizId
     * @return JsonResponse
     */
    public function quizAttemptComparison(int $quizId): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $records = QuizRecord::with(['quiz', 'attempts'])
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->latest()
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No attempts found for this quiz.'
            ], 404);
        }

        $comparison = $records->map(function ($record, $index) {
            return [
                'attempt_number' => $index + 1,
                'score' => $record->percentage ?? 0,
                'correct_answers' => $record->correct_answers,
                'total_questions' => $record->total_questions,
                'status' => $record->percentage >= 70 ? 'Passed' : 'Failed',
                'date' => $record->created_at->format('Y-m-d H:i:s'),
                'elapsed_time' => $record->elapsed_time,
            ];
        });

        $improvement = $comparison->count() > 1
            ? $comparison->last()['score'] - $comparison->first()['score']
            : 0;

        return response()->json([
            'status' => 'success',
            'quiz_id' => $quizId,
            'quiz_title' => $records->first()->quiz->title,
            'total_attempts' => $comparison->count(),
            'improvement' => $improvement,
            'attempts' => $comparison
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * RECORD ENDPOINTS - LEADERSHIP & COMPARISON
     * ────────────────────────────────────────────────────
     */

    /**
     * Get top users based on quiz scores (Global Leaderboard)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $category = $request->get('category');

        $query = QuizRecord::with(['user', 'quiz'])
            ->whereHas('user');

        if ($category) {
            $query->whereHas('quiz', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $leaders = $query
            ->orderByDesc('percentage')
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'name' => "{$record->user->first_name} {$record->user->last_name}",
                    'profile_photo' => $record->user->profile_photo,
                    'score' => $record->score,
                    'percentage' => $record->percentage,
                    'quiz_title' => $record->quiz->title,
                    'category' => $record->quiz->category,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $leaders
        ]);
    }

    /**
     * Get user ranking in global leaderboard
     *
     * @return JsonResponse
     */
    public function userRank(): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userAverageScore = QuizRecord::where('user_id', $user->id)
            ->avg('percentage') ?? 0;

        $rank = QuizRecord::selectRaw('user_id, AVG(percentage) as avg_score')
            ->groupBy('user_id')
            ->having('avg_score', '>', $userAverageScore)
            ->count() + 1;

        $totalUsers = QuizRecord::selectRaw('DISTINCT user_id')
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user->id,
                'rank' => $rank,
                'total_users' => $totalUsers,
                'average_score' => round($userAverageScore, 2),
                'percentile' => round(((($totalUsers - $rank) / $totalUsers) * 100), 2),
            ]
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * RECORD ENDPOINTS - MANAGEMENT
     * ────────────────────────────────────────────────────
     */

    /**
     * Delete a quiz record
     *
     * @param int $recordId
     * @return JsonResponse
     */
    public function deleteRecord(int $recordId): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $record = QuizRecord::where('id', $recordId)
            ->where('user_id', $user->id)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        try {
            $record->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultipleRecords(Request $request): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $recordIds = $request->input('record_ids', []);

        if (empty($recordIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No record IDs provided.'
            ], 400);
        }

        try {
            $deleted = QuizRecord::whereIn('id', $recordIds)
                ->where('user_id', $user->id)
                ->delete();

            return response()->json([
                'status' => 'success',
                'message' => "{$deleted} record(s) deleted successfully.",
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete records.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ────────────────────────────────────────────────────
     * HELPER METHODS
     * ────────────────────────────────────────────────────
     */

    /**
     * Format a QuizRecord with calculated fields
     *
     * @param QuizRecord $record
     * @param bool $includeDetails
     * @return array
     */
    private function formatRecord(QuizRecord $record, bool $includeDetails = false): array
    {
        $questions = $record->attempts->map(function ($attempt) {
            $correctOption = $attempt->question->options->firstWhere('is_correct', true);

            return [
                'question_id' => $attempt->question->id,
                'question' => $attempt->question->question_text,
                'user_answer' => $attempt->selectedOption?->option_text ?? 'No answer',
                'correct_answer' => $correctOption?->option_text ?? 'N/A',
                'is_correct' => (bool) $attempt->is_correct,
            ];
        })->values();

        $totalQuestions = $questions->count();
        $correctAnswers = $questions->where('is_correct', true)->count();
        $percentage = $totalQuestions > 0
            ? round(($correctAnswers / $totalQuestions) * 100)
            : 0;

        $attemptNumber = QuizRecord::where('user_id', $record->user_id)
            ->where('quiz_id', $record->quiz_id)
            ->where('id', '<=', $record->id)
            ->count();

        $baseData = [
            'id' => $record->id,
            'quiz_id' => $record->quiz_id,
            'quiz_title' => $record->quiz?->title ?? 'Untitled Quiz',
            'quiz_description' => $record->quiz?->description ?? null,
            'quiz_type' => $record->quiz?->quiz_type ?? null,
            'category' => $record->quiz?->category ?? null,
            'score' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'percentage' => $percentage,
            'accuracy' => $percentage,
            'status' => $percentage >= 70 ? 'Passed' : 'Failed',
            'attempt' => $attemptNumber,
            'elapsed_time' => $record->elapsed_time,
            'created_at' => $record->created_at,
            'formatted_date' => $record->created_at->format('Y-m-d H:i:s'),
        ];

        if ($includeDetails) {
            $baseData['questions'] = $questions;
        }

        return $baseData;
    }

    /**
     * Apply common filters to record queries
     *
     * @param mixed $query
     * @param Request $request
     * @return void
     */
    private function applyRecordFilters(&$query, Request $request): void
    {
        if ($request->filled('category')) {
            $query->whereHas('quiz', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        if ($request->filled('quiz_type')) {
            $query->whereHas('quiz', function ($q) use ($request) {
                $q->where('quiz_type', $request->quiz_type);
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $query->where(function ($q) use ($status) {
                if ($status === 'passed') {
                    $q->whereRaw('percentage >= 70');
                } elseif ($status === 'failed') {
                    $q->whereRaw('percentage < 70');
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('quiz', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
    }
}