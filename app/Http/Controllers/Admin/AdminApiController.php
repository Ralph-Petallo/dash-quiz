<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Assessment;
use App\Models\Dasher;
use App\Models\Quiz;
use App\Models\QuizRecord;
use App\Services\PerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminApiController extends Controller
{
    private const ADMIN_MIN_ASSESSMENTS = 2; // completed assessment types needed for a COC ranking

    private const ADMIN_LEADER_LIMIT = 10;

    // ##############################################
    // Logging
    // ##############################################
    private function logActivity(?Dasher $admin, string $action, string $description): void
    {
        if (! $admin) {
            return;
        }

        AdminLog::create([
            'admin_id' => $admin->id,
            'action_type' => $action,
            'description' => $description,
        ]);
    }

    // ##############################################
    // DASHBOARD API
    // ##############################################
    public function dashboard(Request $request, PerformanceService $performance): JsonResponse
    {
        $records = QuizRecord::query()
            ->whereNotNull('completed_at')
            ->with('user:id,first_name,last_name,profile_photo')
            ->get();

        // One leaderboard per COC: each learner's best per assessment type, averaged.
        $cocIds = $records->pluck('quiz_id')->unique()->values();

        $cocs = Quiz::query()
            ->whereIn('id', $cocIds)
            ->orderBy('coc_number')
            ->get(['id', 'title', 'coc_number']);

        $cocBoards = [];

        foreach ($cocs as $coc) {
            $cocRecords = $records->where('quiz_id', $coc->id);

            $cocBoards['coc_'.$coc->coc_number] = [
                'coc_number' => $coc->coc_number,
                'assessment_id' => null,
                'label' => "COC {$coc->coc_number}",
                'users' => $this->rankUsers(
                    $cocRecords,
                    fn (QuizRecord $r) => $r->quiz_type,
                    self::ADMIN_MIN_ASSESSMENTS,
                    $performance
                ),
            ];
        }

        $admin = $request->user();

        $activeUsers = Dasher::query()
            ->where('role', 'dasher')
            ->where('active_status', 1)
            ->where('last_activity', '>=', now()->subMinutes(5))
            ->count();

        $logs = AdminLog::query()
            ->latest('id')
            ->limit(100)
            ->get(['id', 'admin_id', 'action_type', 'description', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin_name' => trim("{$admin->first_name} {$admin->last_name}"),
                'total_users' => Dasher::query()->where('role', 'dasher')->count(),
                'total_quizzes' => Quiz::query()->where('quiz_type', 'coc')->count(),
                'total_assessments' => Assessment::query()->count(),
                'active_users' => $activeUsers,
                'logs' => $logs,
                'leaderboards' => [
                    'coc' => $cocBoards,
                    'assessment_types' => $records->pluck('quiz_type')->filter()->unique()->sort()->values(),
                ],
            ],
        ]);
    }

    public function assessmentLeaderboard(string $type, PerformanceService $performance): JsonResponse
    {
        $records = QuizRecord::query()
            ->whereNotNull('completed_at')
            ->where('quiz_type', $type)
            ->with('user:id,first_name,last_name,profile_photo')
            ->get();

        // Same assessment type across COCs: one row per learner, averaged over their best per COC.
        return response()->json([
            'status' => 'success',
            'data' => [
                'type' => $type,
                'users' => $this->rankUsers(
                    $records,
                    fn (QuizRecord $r) => $r->quiz_id,
                    1,
                    $performance
                ),
            ],
        ]);
    }

    /**
     * Rank learners by the average of their best result in each group.
     * Groups are assessment types within a COC, or COCs for one type.
     */
    private function rankUsers(Collection $records, callable $groupKey, int $minCompleted, PerformanceService $performance): array
    {
        return $records
            ->groupBy('user_id')
            ->map(function (Collection $rows) use ($groupKey, $performance) {
                $bests = $rows
                    ->groupBy($groupKey)
                    ->map(fn (Collection $group) => $group->max(fn (QuizRecord $r) => $performance->percentage($r)));

                return [
                    'user' => $rows->first()->user,
                    'completed' => $bests->count(),
                    'average' => $bests->isEmpty() ? 0 : round($bests->avg(), 2),
                ];
            })
            ->filter(fn (array $row) => $row['user'] && $row['completed'] >= $minCompleted)
            ->sortByDesc('average')
            ->take(self::ADMIN_LEADER_LIMIT)
            ->values()
            ->map(fn (array $row) => [
                'id' => (int) $row['user']->id,
                'first_name' => $row['user']->first_name,
                'last_name' => $row['user']->last_name,
                'profile_photo' => $row['user']->profile_photo ?? 'default.png',
                'completed_quizzes' => $row['completed'],
                'average_score' => $row['average'],
            ])
            ->all();
    }

    // ##############################################
    // LOGIN API
    // ##############################################
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('dasher')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = Auth::guard('dasher')->user();

        if (! $user) {
            Auth::guard('dasher')->logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to retrieve authenticated user.',
            ], 401);
        }

        if (! $user->hasVerifiedEmail() && $user->role !== 'admin') {
            Auth::guard('dasher')->logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your email before logging in.',
                'email' => $user->email,
            ], 403);
        }

        $request->session()->regenerate();

        $user->update([
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        Cache::forget('dashquiz:dashboard:stats');
        Cache::forget('dashquiz:leaderboard');

        Cache::put("dashquiz:user:{$user->id}:status", 'online', now()->addMinutes(5));

        return response()->json([
            'status' => 'success',
            'role' => $user->role,
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name,
                'email' => $user->email,
            ],
        ]);
    }

    // ##############################################
    // MOBILE LOGIN API
    // ##############################################
    public function mobileLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = Dasher::where('email', $credentials['email'])
            ->where('role', 'dasher')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your email before logging in.',
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        $user->update([
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        Cache::forget('dashquiz:dashboard:stats');
        Cache::forget('dashquiz:leaderboard');

        Cache::put("dashquiz:user:{$user->id}:status", 'online', now()->addMinutes(5));

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    // ##############################################
    // REGISTER API
    // ##############################################
    public function register(Request $request)
    {
        $valid = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:dasher,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'accept_terms' => ['required', 'accepted'],
        ]);

        $dasher = Dasher::create([
            'first_name' => $valid['first_name'],
            'last_name' => $valid['last_name'],
            'email' => mb_strtolower($valid['email']),
            'password' => Hash::make($valid['password']),
            'role' => 'dasher',
        ]);

        $dasher->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Account created. Check your email for a verification link.',
            'email' => $dasher->email,
        ], 201);
    }

    // ##############################################
    // LOGOUT API
    // ##############################################
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->update([
                'active_status' => 0,
                'last_activity' => now(),
            ]);

            $user->tokens()->delete();

            Cache::forget("dashquiz:user:{$user->id}:status");
            Cache::forget('dashquiz:dashboard:stats');
            Cache::forget('dashquiz:leaderboard');
        }

        Auth::guard('dasher')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    public function heartbeat(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Scheduler already marked offline: force logout.
        if ($user->active_status != 1) {
            $user->tokens()->delete();

            Auth::guard('dasher')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => 'error',
                'message' => 'Session expired',
            ], 403);
        }

        // Throttle DB writes to at most one per 30 seconds.
        if (! $user->last_activity || $user->last_activity->lt(now()->subSeconds(30))) {
            $user->update([
                'last_activity' => now(),
                'active_status' => 1,
            ]);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function createNewAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $valid = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:dasher,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        Dasher::create([
            'first_name' => $valid['first_name'],
            'last_name' => $valid['last_name'],
            'email' => $valid['email'],
            'password' => Hash::make($valid['password']),
            'role' => 'admin',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'New admin created successfully!',
        ], 200);
    }

    // ##############################################
    // USERS TABLE API
    // ##############################################
    public function allUsers(PerformanceService $performance)
    {
        $users = Dasher::query()
            ->where('role', 'dasher')
            ->select(['id', 'email', 'first_name', 'last_name', 'profile_photo', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $recordsByUser = QuizRecord::query()
            ->whereNotNull('completed_at')
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $rows = $users->map(function (Dasher $user) use ($recordsByUser, $performance) {
            $records = $recordsByUser->get($user->id, collect());
            $count = $records->count();

            $average = $count
                ? round($records->avg(fn (QuizRecord $r) => $performance->percentage($r)), 2)
                : null;

            $passed = $records->filter(fn (QuizRecord $r) => $performance->passed($r))->count();

            if ($count === 0) {
                $status = 'not_started';
            } elseif ($count === 1) {
                $status = 'getting_started';
            } elseif ($average < PerformanceService::DEFAULT_PASSING_SCORE) {
                $status = 'needs_improvement';
            } else {
                $status = 'on_track';
            }

            return [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_photo' => $user->profile_photo,
                'created_at' => $user->created_at,
                'quizzes_taken' => $count,
                'passed' => $passed,
                'average_score' => $average,
                'readiness_status' => $status,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $rows], 200);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = Dasher::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('dasher', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Changing the password requires the current one.
        if (! empty($validated['new_password'])) {
            if (! Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['The current password is incorrect.'],
                ]);
            }

            $user->password = Hash::make($validated['new_password']);
        }

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->save();

        $this->logActivity($request->user(), 'Update', "Dasher ID #{$user->id} was updated");

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user->only(['id', 'first_name', 'last_name', 'email']),
        ]);
    }

    public function deleteUser(Request $request, int $id)
    {
        $user = Dasher::findOrFail($id);

        $this->logActivity($request->user(), 'Delete', "User ID {$id} named {$user->first_name} was deleted");

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => "User ID {$id} deleted",
        ], 200);
    }

    // ##############################################
    // STUDENT RECORDS API
    // ##############################################
    public function studentRecords(PerformanceService $performance)
    {
        // Role is enforced by the route middleware (role:admin).
        $records = QuizRecord::query()
            ->with([
                'quiz.assessments',
                'user:id,profile_photo,first_name,last_name',
                'attempts.question.options',
                'attempts.selectedOption',
            ])
            ->orderByRaw('COALESCE(completed_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        $rows = $records
            ->map(fn (QuizRecord $record) => $this->formatRecord($record, $performance, true))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'total' => $rows->count(),
        ], 200);
    }

    /**
     * Same shape and rules as the learner's records endpoint,
     * plus the learner's identity for the admin table.
     */
    private function formatRecord(QuizRecord $record, PerformanceService $performance, bool $includeDetails = false): array
    {
        $quiz = $record->quiz;
        $assessment = $quiz?->assessments?->firstWhere('type', $record->quiz_type);

        $percentage = $performance->percentage($record);
        $passed = $performance->passed($record);

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
                        'question' => $question->question_text ?? 'Question unavailable',
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
            'record_id' => (int) $record->id,
            'user_id' => (int) $record->user_id,

            'user' => $record->user ? [
                'id' => (int) $record->user->id,
                'first_name' => $record->user->first_name,
                'last_name' => $record->user->last_name,
                'profile_photo' => $record->user->profile_photo,
            ] : null,

            'quiz_id' => $record->quiz_id !== null ? (int) $record->quiz_id : null,
            'assessment_id' => $assessment?->id !== null ? (int) $assessment->id : null,
            'coc_id' => $quiz?->id !== null ? (int) $quiz->id : null,
            'coc_number' => $quiz?->coc_number,

            // Assessment title first, so admin and learner show the same name.
            'quiz_title' => $assessment?->title ?? $quiz?->title ?? 'Untitled Assessment',
            'quiz_description' => $assessment?->description ?? $quiz?->description,
            'quiz_type' => $record->quiz_type ?? $assessment?->type ?? 'unknown',
            'category' => $record->category ?? $quiz?->category ?? $assessment?->category,

            'score' => (int) ($record->score ?? 0),
            'correct_answers' => (int) ($record->correct_answers ?? 0),
            'total_questions' => (int) ($record->total_questions ?? 0),
            'percentage' => $percentage,
            'accuracy' => $percentage,
            'passing_score' => (float) ($record->passing_score ?? PerformanceService::DEFAULT_PASSING_SCORE),
            'passed' => $passed,
            'status' => $passed ? 'Passed' : 'Failed',

            'attempt_count' => max(1, (int) ($record->attempt_count ?? 1)),
            'attempt' => max(1, (int) ($record->attempt_count ?? 1)),

            'elapsed_time' => $record->elapsed_time !== null ? (int) $record->elapsed_time : null,

            'review_type' => $questions->isNotEmpty() ? 'questions' : 'summary',
            'questions' => $includeDetails ? $questions->all() : [],

            'created_at' => $record->created_at?->toISOString(),
            'completed_at' => ($record->completed_at ?? $record->created_at)?->toISOString(),
            'updated_at' => $record->updated_at?->toISOString(),
            'formatted_date' => ($record->completed_at ?? $record->created_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
