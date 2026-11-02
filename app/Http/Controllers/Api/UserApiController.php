<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Dasher;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Services\PerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserApiController extends Controller
{
    private const PASSING_SCORE = 75.00;

    private const LEADERBOARD_SCAN_LIMIT = 2000;

    public function __construct(private PerformanceService $performance) {}
    /**
     * ────────────────────────────────────────────────────

     * USER PROFILE ENDPOINTS

     * ────────────────────────────────────────────────────
     */

    /**
     * Get authenticated user profile with statistics
     */

    /**
     * Get authenticated user profile with statistics
     */
    public function profile(): JsonResponse
    {
        // Same guard as the other API endpoints, so auth behaves the same everywhere.
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Tracking activity should never block the profile from loading.
        try {
            $user->forceFill([
                'active_status' => true,
                'last_activity' => now(),
            ])->saveQuietly();
        } catch (\Throwable $e) {
            \Log::warning('Failed to update user activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Same summary used by Home, Records, and the leaderboard.
        $summary = $this->performance->summary(userId: (int) $user->id);

        return response()->json([
            'status' => 'success',
            'results' => [
                'id' => (int) $user->id,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
                'profile_photo' => $user->profile_photo ?? 'default.png',
                'created_at' => $user->created_at?->format('F j, Y'),
                'last_activity' => $user->last_activity?->toISOString(),

                'statistics' => [
                    'total_attempts' => $summary['total_attempts'],
                    'passed' => $summary['passed'],
                ],
            ],
        ]);
    }

    /**
     * ────────────────────────────────────────────────────

     * QUIZ ENDPOINTS

     * ────────────────────────────────────────────────────
     */

    /**
     * Get list of available assessments with metadata
     */
    public function coc(): JsonResponse
    {
        $cocs = Quiz::query()
            ->where('quiz_type', 'coc')
            ->with([
                'assessments' => function ($query) {
                    $query->orderByDesc('available')
                        ->orderBy('id');
                },
            ])
            ->withCount([
                'assessments',
                'assessments as available_assessments_count' => function ($query) {
                    $query->where('available', true);
                },
            ])
            ->orderBy('coc_number')
            ->get()
            ->map(function (Quiz $coc) {
                return [
                    'id' => (int) $coc->id,
                    'title' => $coc->title,
                    'description' => $coc->description,
                    'quiz_type' => $coc->quiz_type,
                    'coc_number' => (int) $coc->coc_number,
                    'difficulty' => $coc->difficulty,
                    'category' => $coc->category,

                    'assessment_count' => (int) $coc->assessments_count,
                    'available_assessment_count' => (int) $coc->available_assessments_count,

                    'assessments' => $coc->assessments
                        ->map(function (Assessment $assessment) {
                            return [
                                'id' => (int) $assessment->id,
                                'type' => $assessment->type,
                                'title' => $assessment->title,
                                'icon' => $assessment->icon,
                                'description' => $assessment->description,
                                'category' => $assessment->category,
                                'difficulty' => $assessment->difficulty,
                                'meta' => $assessment->meta ?? [],
                                'path' => $assessment->path,
                                'available' => (bool) $assessment->available,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $cocs,
            'meta' => [
                'qualification' => 'Computer Systems Servicing NC II',
                'system' => 'CSS Prep',
                'coc_count' => $cocs->count(),
                'available_assessments' => $cocs->sum('available_assessment_count'),
            ],
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
     */

    // Get assessment history of the logged-in user

    public function records(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $perPage = max(5, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        $query = QuizRecord::query()
            ->with([
                'quiz.assessments',
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
            ->map(fn (QuizRecord $record) => $this->formatRecord($record, true))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
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
     */
    public function recordsPaginated(Request $request): JsonResponse
    {
        // Backward-compatible alias. The main records() endpoint is now paginated.
        return $this->records($request);
    }

    /**
     * Get single record by ID
     */
    public function preview(int $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
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

        if (! $record) {
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

    /**
     * Get recent quiz records
     */
    public function recentRecords(Request $request): JsonResponse
    {

        $user = auth('sanctum')->user();

        if (! $user) {

            return response()->json([

                'status' => 'error',

                'message' => 'Unauthenticated.',

            ], 401);

        }

        $limit = $request->get('limit', 5);

        $records = QuizRecord::with([

            'quiz',

            'attempts.question.options',

            'attempts.selectedOption',

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

            'data' => $records,

        ]);

    }

    /**
     * Get records filtered by category
     */
    public function recordsByCategory(string $category): JsonResponse
    {

        /** @var Dasher|null $user */
        $user = auth('sanctum')->user();

        if (! $user) {

            return response()->json([

                'status' => 'error',

                'message' => 'Unauthenticated.',

            ], 401);

        }

        $records = QuizRecord::with([

            'quiz',

            'attempts.question.options',

            'attempts.selectedOption',

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

            'data' => $records,

        ]);

    }

    /**
     * Get records for a specific quiz (all attempts)
     */
    public function recordsByQuiz(int $quizId): JsonResponse
    {

        $user = auth('sanctum')->user();

        if (! $user) {

            return response()->json([

                'status' => 'error',

                'message' => 'Unauthenticated.',

            ], 401);

        }

        $records = QuizRecord::with([

            'quiz',

            'attempts.question.options',

            'attempts.selectedOption',

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

                'message' => 'No records found for this quiz.',

            ], 404);

        }

        return response()->json([

            'status' => 'success',

            'quiz_id' => $quizId,

            'total_attempts' => $records->count(),

            'data' => $records,

        ]);

    }

    /**
     * Get comparison between attempts on same quiz
     */
    public function quizAttemptComparison(int $quizId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Attempts are summarised from quiz_records only, so no question rows are loaded.
        $records = QuizRecord::with('quiz:id,title')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->latest('id')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No attempts found for this quiz.',
            ], 404);
        }

        $comparison = $records->values()->map(function (QuizRecord $record, int $index) {
            $date = $record->completed_at ?? $record->created_at;

            return [
                'attempt_number' => $index + 1,
                'quiz_type' => $record->quiz_type,
                'score' => $this->performance->percentage($record),
                'correct_answers' => (int) $record->correct_answers,
                'total_questions' => (int) $record->total_questions,
                'passed' => $this->performance->passed($record),
                'status' => $this->performance->passed($record) ? 'Passed' : 'Failed',
                'date' => $date?->format('Y-m-d H:i:s'),
                'elapsed_time' => $record->elapsed_time !== null ? (int) $record->elapsed_time : null,
            ];
        });

        // Oldest to newest, so "improvement" means later minus earlier.
        $ordered = $comparison->reverse()->values();

        $improvement = $ordered->count() > 1
            ? round($ordered->last()['score'] - $ordered->first()['score'], 2)
            : 0;

        return response()->json([
            'status' => 'success',
            'quiz_id' => $quizId,
            'quiz_title' => $records->first()->quiz?->title ?? 'Assessment',
            'total_attempts' => $ordered->count(),
            'improvement' => $improvement,
            'attempts' => $ordered,
        ]);
    }

    /**
     * ────────────────────────────────────────────────────

     * RECORD ENDPOINTS - LEADERSHIP & COMPARISON

     * ────────────────────────────────────────────────────
     */

    /**
     * Get top users based on quiz scores (Global Leaderboard)
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assessment_id' => ['nullable', 'integer', 'exists:assessments,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) ($validated['limit'] ?? 100);

        // Assessments that have at least one completed result.
        $assessments = Assessment::query()
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('quiz_records')
                    ->whereColumn('quiz_records.quiz_id', 'assessments.coc_id')
                    ->whereColumn('quiz_records.quiz_type', 'assessments.type')
                    ->whereNotNull('quiz_records.completed_at');
            })
            ->with('coc:id,title,coc_number')
            ->orderBy('title')
            ->get();

        $filterOptions = [
            'cocs' => $assessments->pluck('coc')->filter()->unique('id')->sortBy('coc_number')->values()
                ->map(fn (Quiz $coc) => [
                    'id' => (int) $coc->id,
                    'label' => $coc->coc_number !== null ? "COC {$coc->coc_number} · {$coc->title}" : $coc->title,
                ]),

            'assessments' => $assessments->map(fn (Assessment $a) => [
                'id' => (int) $a->id,
                'coc_id' => (int) $a->coc_id,
                'type' => $a->type,
                'title' => $a->title,
            ]),
        ];

        $selected = $assessments->firstWhere('id', $validated['assessment_id'] ?? null);

        if (! $selected) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'meta' => [
                    'returned' => 0,
                    'limit' => $limit,
                    'filters' => ['assessment_id' => null],
                    'filter_options' => $filterOptions,
                ],
            ]);
        }

        $currentUserId = (int) $request->user()->id;
        $leaders = $this->performance
            ->leaderboard((int) $selected->coc_id, $selected->type, $limit)
            ->values()
            ->map(function (QuizRecord $record, int $index) use ($currentUserId) {
                $user = $record->user;
                $name = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
                $passed = $this->performance->passed($record);

                return [
                    'rank' => $index + 1,
                    'is_you' => (int) $record->user_id === $currentUserId,
                    'name' => $name !== '' ? $name : 'Student',
                    'profile_photo' => $user?->profile_photo,
                    'score' => (int) ($record->score ?? 0),
                    'percentage' => $this->performance->percentage($record),
                    'passed' => $passed,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $leaders,
            'meta' => [
                'returned' => $leaders->count(),
                'limit' => $limit,
                'filters' => ['assessment_id' => (int) $selected->id],
                'filter_options' => $filterOptions,
            ],
        ]);
    }

    /**
     * ────────────────────────────────────────────────────
     * RECORD ENDPOINTS - MANAGEMENT
     * ────────────────────────────────────────────────────
     */
    /**
     * Delete a quiz record
     */
    public function deleteRecord(int $recordId): JsonResponse
    {

        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);

        }

        $record = QuizRecord::where('id', $recordId)
            ->where('user_id', $user->id)
            ->first();

        if (! $record) {

            return response()->json([

                'status' => 'error',

                'message' => 'Record not found.',

            ], 404);

        }

        try {

            $record->delete();

            return response()->json([

                'status' => 'success',

                'message' => 'Record deleted successfully.',

            ]);

        } catch (\Exception $e) {

            return response()->json([

                'status' => 'error',

                'message' => 'Failed to delete record.',

                'error' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Delete multiple records
     */
    public function deleteMultipleRecords(Request $request): JsonResponse
    {

        $user = auth('sanctum')->user();

        if (! $user) {

            return response()->json([

                'status' => 'error',

                'message' => 'Unauthenticated.',

            ], 401);

        }

        $recordIds = $request->input('record_ids', []);

        if (empty($recordIds)) {

            return response()->json([

                'status' => 'error',

                'message' => 'No record IDs provided.',

            ], 400);

        }

        try {

            $deleted = QuizRecord::whereIn('id', $recordIds)

                ->where('user_id', $user->id)

                ->delete();

            return response()->json([

                'status' => 'success',

                'message' => "{$deleted} record(s) deleted successfully.",

                'deleted_count' => $deleted,

            ]);

        } catch (\Exception $e) {

            return response()->json([

                'status' => 'error',

                'message' => 'Failed to delete records.',

                'error' => $e->getMessage(),

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
     */
    private function formatRecord(QuizRecord $record, bool $includeDetails = false): array
    {
        // quizzes.id (the COC-level quiz this record belongs to)
        $quiz = $record->quiz;

        // assessments.id, matched by type so each record gets its own assessment
        $assessment = $quiz?->assessments?->firstWhere('type', $record->quiz_type);

        $correctAnswers = (int) ($record->correct_answers ?? $record->score ?? 0);
        $score = (int) ($record->score ?? $correctAnswers);
        $totalQuestions = (int) ($record->total_questions ?? 0);

        // Same rules as Home, the leaderboard, and the profile.
        $percentage = $this->performance->percentage($record);
        $passed = $this->performance->passed($record);
        $passingScore = (float) ($record->passing_score ?? PerformanceService::DEFAULT_PASSING_SCORE);
        $status = $passed ? 'Passed' : 'Failed';

        $questions = collect();

        if ($includeDetails) {
            $questions = $record->attempts
                ->map(function ($attempt) {
                    $question = $attempt->question;

                    if (! $question) {
                        return null;
                    }

                    $correctOption = $question->options?->firstWhere('is_correct', true);

                    return [
                        'question_id' => (int) $question->id,
                        'question' => $question->question_text
                            ?? $question->text
                            ?? 'Question unavailable',
                        'user_answer' => $attempt->selectedOption?->option_text
                            ?? $attempt->selectedOption?->text
                            ?? 'No answer',
                        'correct_answer' => $correctOption?->option_text
                            ?? $correctOption?->text
                            ?? 'N/A',
                        'is_correct' => (bool) $attempt->is_correct,
                    ];
                })
                ->filter()
                ->values();
        }

        return [
            'id' => (int) $record->id,
            'record_id' => (int) $record->id,
            'user_id' => (int) $record->user_id,

            // quizzes.id
            'quiz_id' => $record->quiz_id !== null ? (int) $record->quiz_id : null,

            // assessments.id
            'assessment_id' => $assessment?->id !== null ? (int) $assessment->id : null,

            // COC identifiers
            'coc_id' => $quiz?->id !== null ? (int) $quiz->id : null,
            'coc_number' => $quiz?->coc_number,

            // Prefer the assessment's own title and description. Both assessments
            // under one COC share the quiz title, so the COC title would be ambiguous.
            'quiz_title' => $assessment?->title ?? $quiz?->title ?? 'Untitled Assessment',
            'quiz_description' => $assessment?->description ?? $quiz?->description,

            'quiz_type' => $record->quiz_type ?? $quiz?->quiz_type ?? $assessment?->type ?? 'unknown',

            'category' => $record->category ?? $quiz?->category ?? $assessment?->category,

            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'percentage' => $percentage,
            'accuracy' => $percentage,
            'passing_score' => $passingScore,
            'passed' => $passed,
            'status' => $status,

            'attempt_count' => max(1, (int) ($record->attempt_count ?? 1)),

            'elapsed_time' => $record->elapsed_time !== null ? (int) $record->elapsed_time : null,

            'review_type' => $questions->isNotEmpty() ? 'questions' : 'summary',
            'questions' => $includeDetails ? $questions->all() : [],

            'created_at' => $record->created_at?->toISOString(),
            'completed_at' => ($record->completed_at ?? $record->created_at)?->toISOString(),
            'updated_at' => $record->updated_at?->toISOString(),
            'formatted_date' => ($record->completed_at ?? $record->created_at)?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply common filters to record queries

     *

     * @param  mixed  $query
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
                            ->whereHas('quiz', fn ($quizQuery) => $quizQuery->where('category', $category));
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
                            ->whereHas('quiz', fn ($quizQuery) => $quizQuery->where('quiz_type', $quizType));
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
            ->map(fn (QuizRecord $record) => $record->quiz_type ?: $record->quiz?->quiz_type)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $categories = $records
            ->map(fn (QuizRecord $record) => $record->category ?: $record->quiz?->category)
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
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Same summary the profile, admin, and leaderboard pages will use.
        $summary = $this->performance->summary(userId: (int) $user->id);

        $recentActivity = QuizRecord::query()
            ->with('quiz:id,title,coc_number,quiz_type,category')
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (QuizRecord $record) {
                $passed = $this->performance->passed($record);

                return [
                    'id' => (int) $record->id,
                    'quiz_id' => (int) $record->quiz_id,
                    'quiz_title' => $record->quiz?->title ?? 'Assessment',
                    'quiz_type' => $record->quiz_type ?: 'assessment',
                    'category' => $record->category ?: $record->quiz?->category,
                    'coc_number' => $record->quiz?->coc_number,
                    'percentage' => $this->performance->percentage($record),
                    'passing_score' => (float) (
                        $record->passing_score
                        ?? PerformanceService::DEFAULT_PASSING_SCORE
                    ),
                    'passed' => $passed,
                    'status' => $passed ? 'Passed' : 'Failed',
                    'elapsed_time' => $record->elapsed_time !== null
                        ? (int) $record->elapsed_time
                        : null,
                    'attempt_count' => (int) ($record->attempt_count ?? 1),
                    'completed_at' => $record->completed_at?->toISOString(),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }
}
