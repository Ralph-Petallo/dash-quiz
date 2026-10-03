<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;
use App\Models\Dasher;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Models\Rj45Assessment;
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

     * @param Request request

     * @return JsonResponse

     */

    // Get assessment history of the logged-in user

    public function records(Request $request): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $perPage = max(5, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        $query = QuizRecord::query()
            ->with([
                'quiz',
                'attempts.question.options',
                'attempts.selectedOption',
            ])
            ->where('user_id', $user->id);

        $this->applyRecordFilters($query, $request);

        $paginator = $query
            ->orderByRaw('COALESCE(completed_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())
            ->map(fn(QuizRecord $record) => $this->formatRecord($record, true))
            ->values();

        return response()->json([
            'status' => 'success',

            // New aligned response.
            'data' => $rows,

            // Temporary compatibility for any old page still reading `results`.
            'results' => $rows,

            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => max(1, $paginator->lastPage()),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
                'has_more' => $paginator->hasMorePages(),
            ],

            'filter_options' => $this->recordFilterOptions((int) $user->id),

            'user' => [
                'id' => (int) $user->id,
                'full_name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
            ],
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
        // Backward-compatible alias. The main records() endpoint is now paginated.
        return $this->records($request);
    }



    /**

     * Get single record by ID

     *

     * @param int $id

     * @return JsonResponse

     */

    public function record(int $id): JsonResponse
    {
        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $record = QuizRecord::query()
            ->with([
                'quiz',
                'attempts.question.options',
                'attempts.selectedOption',
            ])
            ->whereKey($id)
            ->where('user_id', $user->id)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatRecord($record, true),
        ]);
    }

    public function preview(int $id): JsonResponse
    {
        // Backward-compatible alias for older frontend code.
        return $this->record($id);
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
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
         * All supported assessments now write their official result to
         * quiz_records. The assessments table is configuration metadata,
         * so it must not be queried by user_id for performance history.
         */
        $records = QuizRecord::query()
            ->with('quiz')
            ->where('user_id', $user->id)
            ->get();

        $isPassed = function (QuizRecord $record): bool {
            $percentage = (float) ($record->percentage ?? 0);
            $passingScore = (float) ($record->passing_score ?? $record->quiz?->passing_score ?? 70);
            $status = strtolower((string) ($record->status ?? ''));

            if (str_contains($status, 'pass') || str_contains($status, 'complete')) {
                return true;
            }

            if (str_contains($status, 'fail')) {
                return false;
            }

            return $percentage >= $passingScore;
        };

        $total = $records->count();
        $passed = $records->filter($isPassed)->count();
        $failed = $total - $passed;
        $average = (float) ($records->avg('percentage') ?? 0);
        $best = (float) ($records->max('percentage') ?? 0);
        $worst = (float) ($records->min('percentage') ?? 0);

        $byCategory = $records
            ->groupBy(fn(QuizRecord $record) => $record->category ?: $record->quiz?->category ?: 'Uncategorized')
            ->map(function ($group) use ($isPassed) {
                $groupTotal = $group->count();
                $groupPassed = $group->filter($isPassed)->count();

                return [
                    'total' => $groupTotal,
                    'average_score' => round((float) ($group->avg('percentage') ?? 0), 2),
                    'passed' => $groupPassed,
                    'failed' => $groupTotal - $groupPassed,
                    'pass_rate' => $groupTotal > 0
                        ? round(($groupPassed / $groupTotal) * 100, 2)
                        : 0,
                ];
            });

        $byType = $records
            ->groupBy(fn(QuizRecord $record) => $record->quiz_type ?: $record->quiz?->quiz_type ?: 'assessment')
            ->map(function ($group) use ($isPassed) {
                $groupTotal = $group->count();
                $groupPassed = $group->filter($isPassed)->count();

                return [
                    'total' => $groupTotal,
                    'average_score' => round((float) ($group->avg('percentage') ?? 0), 2),
                    'passed' => $groupPassed,
                    'failed' => $groupTotal - $groupPassed,
                    'pass_rate' => $groupTotal > 0
                        ? round(($groupPassed / $groupTotal) * 100, 2)
                        : 0,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => (int) $user->id,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                ],
                'overview' => [
                    'total_assessments' => $total,
                    'passed' => $passed,
                    'failed' => $failed,
                    'pass_rate' => $total > 0
                        ? round(($passed / $total) * 100, 2)
                        : 0,
                ],
                'quiz' => [
                    'total' => $total,
                    'passed' => $passed,
                    'failed' => $failed,
                    'pass_rate' => $total > 0
                        ? round(($passed / $total) * 100, 2)
                        : 0,
                    'average' => round($average, 2),
                    'best' => $best,
                    'worst' => $worst,
                    'by_category' => $byCategory,
                    'by_type' => $byType,
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

        $limit = max(1, min((int) $request->get('limit', 200), 500));



        $category = $request->filled('category')

            ? trim((string) $request->get('category'))

            : null;



        $quizId = $request->filled('quiz_id')

            ? (int) $request->get('quiz_id')

            : null;



        $query = QuizRecord::query()

            ->with([

                'user',

                'quiz',

            ])

            ->whereHas('user')

            ->whereHas('quiz')

            ->whereNotNull('completed_at');



        if ($quizId) {

            $query->where('quiz_id', $quizId);

        }



        if ($category) {

            $query->where(function ($q) use ($category) {

                $q->where('category', $category)

                    ->orWhereHas('quiz', function ($quizQuery) use ($category) {

                        $quizQuery->where('category', $category);

                    });

            });

        }



        $records = $query

            ->orderByDesc('percentage')

            ->orderByDesc('score')

            ->orderByRaw(

                'CASE WHEN elapsed_time IS NULL THEN 1 ELSE 0 END ASC'

            )

            ->orderBy('elapsed_time')

            ->orderByDesc('completed_at')

            ->orderByDesc('id')

            ->limit($limit)

            ->get();



        $leaders = $records

            ->map(function (QuizRecord $record) {

                $user = $record->user;

                $quiz = $record->quiz;



                $fullName = trim(

                    ((string) ($user?->first_name ?? ''))

                    . ' '

                    . ((string) ($user?->last_name ?? ''))

                );



                return [

                    'id' => (int) $record->id,

                    'record_id' => (int) $record->id,



                    'user_id' => (int) $record->user_id,

                    'name' => $fullName !== ''

                        ? $fullName

                        : 'Student',

                    'profile_photo' => $user?->profile_photo,



                    'quiz_id' => (int) $record->quiz_id,

                    'quiz_title' => $quiz?->title ?? 'Assessment',

                    'quiz_type' => $record->quiz_type

                        ?: $quiz?->quiz_type,

                    'category' => $record->category

                        ?: $quiz?->category,

                    'coc_number' => $quiz?->coc_number,



                    'score' => (int) ($record->score ?? 0),

                    'correct_answers' => (int) ($record->correct_answers ?? 0),

                    'total_questions' => (int) ($record->total_questions ?? 0),

                    'percentage' => (float) ($record->percentage ?? 0),

                    'passing_score' => (float) ($record->passing_score ?? 75),

                    'status' => $record->status,



                    'attempt_count' => (int) ($record->attempt_count ?? 0),

                    'elapsed_time' => $record->elapsed_time !== null

                        ? (int) $record->elapsed_time

                        : null,



                    'completed_at' => optional(

                        $record->completed_at

                    )?->toISOString(),

                ];

            })

            ->values();



        return response()->json([

            'status' => 'success',

            'data' => $leaders,

            'meta' => [

                'returned' => $leaders->count(),

                'limit' => $limit,

                'filters' => [

                    'category' => $category,

                    'quiz_id' => $quizId,

                ],

            ],

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
        $quiz = $record->quiz;

        $correctAnswers = (int) ($record->correct_answers ?? $record->score ?? 0);
        $score = (int) ($record->score ?? $correctAnswers);
        $totalQuestions = (int) ($record->total_questions ?? 0);

        $percentage = $record->percentage !== null
            ? (float) $record->percentage
            : ($totalQuestions > 0
                ? round(($correctAnswers / $totalQuestions) * 100, 2)
                : 0.0);

        $passingScore = (float) (
            $record->passing_score
            ?? $quiz?->passing_score
            ?? 70
        );

        $status = $record->status
            ?: ($percentage >= $passingScore ? 'Passed' : 'Failed');

        $questions = collect();

        if ($includeDetails) {
            $questions = $record->attempts
                ->map(function ($attempt) {
                    $question = $attempt->question;

                    if (!$question) {
                        return null;
                    }

                    $correctOption = $question->options
                        ? $question->options->firstWhere('is_correct', true)
                        : null;

                    return [
                        'question_id' => (int) $question->id,
                        'question' => $question->question_text,
                        'user_answer' => $attempt->selectedOption?->option_text ?? 'No answer',
                        'correct_answer' => $correctOption?->option_text ?? 'N/A',
                        'is_correct' => (bool) $attempt->is_correct,
                    ];
                })
                ->filter()
                ->values();
        }

        return [
            'id' => (int) $record->id,
            'user_id' => (int) $record->user_id,
            'quiz_id' => (int) $record->quiz_id,

            // COC is a quiz property. quiz_id is NOT the COC number.
            'coc_number' => $quiz?->coc_number,

            // Temporary compatibility for old frontend references.
            'coc_id' => $quiz?->coc_number,

            'quiz_title' => $quiz?->title ?? 'Untitled Assessment',
            'quiz_description' => $quiz?->description,

            'quiz_type' => $record->quiz_type
                ?: $quiz?->quiz_type
                ?: 'assessment',

            'category' => $record->category
                ?: $quiz?->category,

            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'percentage' => $percentage,
            'accuracy' => $percentage,
            'passing_score' => $passingScore,
            'status' => $status,

            'attempt_count' => max(1, (int) ($record->attempt_count ?? 1)),
            'attempt' => max(1, (int) ($record->attempt_count ?? 1)),
            'elapsed_time' => $record->elapsed_time !== null
                ? (int) $record->elapsed_time
                : null,

            'review_type' => $questions->isNotEmpty()
                ? 'questions'
                : 'summary',

            'questions' => $includeDetails
                ? $questions->all()
                : [],

            'created_at' => $record->created_at?->toISOString(),
            'completed_at' => ($record->completed_at ?? $record->created_at)?->toISOString(),
            'updated_at' => $record->updated_at?->toISOString(),
            'formatted_date' => ($record->completed_at ?? $record->created_at)?->format('Y-m-d H:i:s'),
        ];
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
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));

            $query->where(function ($recordQuery) use ($search) {
                $recordQuery
                    ->where('category', 'LIKE', "%{$search}%")
                    ->orWhere('quiz_type', 'LIKE', "%{$search}%")
                    ->orWhereHas('quiz', function ($quizQuery) use ($search) {
                        $quizQuery
                            ->where('title', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%")
                            ->orWhere('category', 'LIKE', "%{$search}%")
                            ->orWhere('quiz_type', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category')) {
            $category = trim((string) $request->get('category'));

            $query->where(function ($recordQuery) use ($category) {
                $recordQuery
                    ->where('category', $category)
                    ->orWhere(function ($fallback) use ($category) {
                        $fallback
                            ->whereNull('category')
                            ->whereHas('quiz', fn($quizQuery) => $quizQuery->where('category', $category));
                    });
            });
        }

        if ($request->filled('quiz_type')) {
            $quizType = trim((string) $request->get('quiz_type'));

            $query->where(function ($recordQuery) use ($quizType) {
                $recordQuery
                    ->where('quiz_type', $quizType)
                    ->orWhere(function ($fallback) use ($quizType) {
                        $fallback
                            ->whereNull('quiz_type')
                            ->whereHas('quiz', fn($quizQuery) => $quizQuery->where('quiz_type', $quizType));
                    });
            });
        }

        if ($request->filled('date')) {
            $date = (string) $request->get('date');

            $query->where(function ($dateQuery) use ($date) {
                $dateQuery
                    ->whereDate('completed_at', $date)
                    ->orWhere(function ($fallback) use ($date) {
                        $fallback
                            ->whereNull('completed_at')
                            ->whereDate('created_at', $date);
                    });
            });
        }

        // Keep support for existing callers that still send date_from/date_to.
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->get('status')));

            if (in_array($status, ['passed', 'failed'], true)) {
                $query->whereRaw('LOWER(status) = ?', [$status]);
            }
        }
    }



    private function recordFilterOptions(int $userId): array
    {
        $records = QuizRecord::query()
            ->with('quiz:id,quiz_type,category')
            ->where('user_id', $userId)
            ->get([
                'id',
                'quiz_id',
                'quiz_type',
                'category',
            ]);

        $types = $records
            ->map(fn(QuizRecord $record) => $record->quiz_type ?: $record->quiz?->quiz_type)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $categories = $records
            ->map(fn(QuizRecord $record) => $record->category ?: $record->quiz?->category)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'types' => $types,
            'categories' => $categories,
        ];
    }

    /*
|--------------------------------------------------------------------------
| Add this method inside App\Http\Controllers\Api\UserApiController
|--------------------------------------------------------------------------
*/

   public function dashboardOverview(): JsonResponse
{
    /** @var Dasher|null $user */
    $user = auth('sanctum')->user();

    if (! $user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $records = QuizRecord::query()
        ->with('quiz')
        ->where('user_id', $user->id)
        ->orderByDesc('completed_at')
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->get();

    $percentageFor = static function (QuizRecord $record): float {
        if ($record->percentage !== null) {
            return max(0, min(100, (float) $record->percentage));
        }

        $correct = (int) (
            $record->correct_answers
            ?? $record->score
            ?? 0
        );

        $total = (int) ($record->total_questions ?? 0);

        if ($total <= 0) {
            return 0.0;
        }

        return round(($correct / $total) * 100, 2);
    };

    $passingScoreFor = static function (QuizRecord $record): float {
        return (float) (
            $record->passing_score
            ?? $record->quiz?->passing_score
            ?? 70
        );
    };

    $isPassed = static function (QuizRecord $record) use (
        $percentageFor,
        $passingScoreFor
    ): bool {
        $status = strtolower(trim((string) ($record->status ?? '')));

        if (str_contains($status, 'pass')) {
            return true;
        }

        if (
            str_contains($status, 'fail') ||
            str_contains($status, 'incomplete')
        ) {
            return false;
        }

        return $percentageFor($record) >= $passingScoreFor($record);
    };

    $percentages = $records->map(
        fn (QuizRecord $record) => $percentageFor($record)
    );

    $totalAttempts = $records->count();

    $averageScore = $percentages->isNotEmpty()
        ? round((float) $percentages->avg(), 1)
        : 0.0;

    $bestScore = $percentages->isNotEmpty()
        ? round((float) $percentages->max(), 1)
        : 0.0;

    $passedAttempts = $records->filter($isPassed)->count();

    $recentActivity = $records
        ->take(5)
        ->map(function (QuizRecord $record) use (
            $percentageFor,
            $passingScoreFor,
            $isPassed
        ) {
            $percentage = $percentageFor($record);
            $passingScore = $passingScoreFor($record);
            $status = trim((string) ($record->status ?? ''));

            if ($status === '') {
                $status = $isPassed($record)
                    ? 'passed'
                    : 'failed';
            }

            return [
                'id' => (int) $record->id,
                'quiz_id' => (int) $record->quiz_id,
                'quiz_title' => $record->quiz?->title ?? 'Assessment',
                'quiz_type' => $record->quiz_type
                    ?: $record->quiz?->quiz_type
                    ?: 'assessment',
                'category' => $record->category
                    ?: $record->quiz?->category,
                'coc_number' => $record->quiz?->coc_number,
                'score' => (int) (
                    $record->score
                    ?? $record->correct_answers
                    ?? 0
                ),
                'correct_answers' => (int) (
                    $record->correct_answers
                    ?? $record->score
                    ?? 0
                ),
                'total_questions' => (int) (
                    $record->total_questions
                    ?? 0
                ),
                'percentage' => $percentage,
                'passing_score' => $passingScore,
                'status' => $status,
                'elapsed_time' => $record->elapsed_time !== null
                    ? (int) $record->elapsed_time
                    : null,
                'attempt_count' => (int) (
                    $record->attempt_count
                    ?? 1
                ),
                'completed_at' => optional(
                    $record->completed_at
                    ?? $record->created_at
                )?->toISOString(),
            ];
        })
        ->values();

    return response()->json([
        'status' => 'success',
        'data' => [
            'summary' => [
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'passed' => $passedAttempts,
                'total_attempts' => $totalAttempts,
            ],
            'recent_activity' => $recentActivity,
        ],
    ]);
}


}