<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Dasher;
use App\Models\QuizRecord;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;


class AdminApiController extends Controller
{
    ###############################################
    # Logging API
    ###############################################
    public function logActivity(string $action, string $description)
    {
        $admin = Auth::guard('dasher')->user();
        // insert log
        AdminLog::create([
            'admin_id' => $admin->id,
            'action_type' => $action,
            'description' => $description,
        ]);
    }
    ###############################################
    # DASHBOARD DATA API
    ###############################################

    public function dashboard()
    {
        $admin = Auth::guard('dasher')->user();

        if (!$admin || $admin->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $stats = Cache::remember(
            'admin:dashboard:stats',
            now()->addMinutes(5),
            function () use ($admin) {

                $totalUsers = Dasher::where('role', 'dasher')->count();

                $totalQuizzes = Quiz::count();

                $activeCount = Dasher::where('role', 'dasher')
                    ->where('last_activity', '>=', now()->subMinutes(1))
                    ->count();

                $topDashers = Dasher::where('role', 'dasher')
                    ->withCount('quizRecords as total_quizzes')
                    ->withSum('quizRecords as total_score', 'score')
                    ->get()
                    ->map(function ($dasher) {
                        $average = $dasher->total_quizzes
                            ? round(
                                ($dasher->total_score / ($dasher->total_quizzes * 10)) * 100,
                                1
                            )
                            : 0;

                        return [
                            'id' => $dasher->id,
                            'first_name' => $dasher->first_name,
                            'last_name' => $dasher->last_name,
                            'average_score' => $average,
                            'profile_photo' => $dasher->profile_photo,
                        ];
                    })
                    ->sortByDesc('average_score')
                    ->take(10)
                    ->values();

                $logs = AdminLog::latest()
                    ->take(20)
                    ->get();

                return [
                    'total_users' => $totalUsers,
                    'total_quizzes' => $totalQuizzes,
                    'active_users' => $activeCount,
                    'logs' => $logs,
                    'top_users' => $topDashers,
                ];
            }
        );

        // Admin name should NOT be cached
        $stats['admin_name'] = $admin->first_name;

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
    ###############################################
    # LOGIN API
    ###############################################
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('dasher')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $request->session()->regenerate();

        /** @var Dasher $user */
        $user = Auth::guard('dasher')->user();

        $user->update([
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        Cache::forget('dashquiz:dashboard:stats');
        Cache::forget('dashquiz:leaderboard');

        Cache::put(
            "dashquiz:user:{$user->id}:status",
            'online',
            now()->addMinutes(5)
        );

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

    ###############################################
    # MOBILE LOGIN API 
    ###############################################
    public function mobileLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = Dasher::where('email', $credentials['email'])
            ->where('role', 'dasher')
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // create token
        $token = $user->createToken('mobile')->plainTextToken;

        // update user activity
        $user->update([
            'active_status' => 1,
            'last_activity' => now(),
        ]);

        Cache::forget('dashquiz:dashboard:stats');
        Cache::forget('dashquiz:leaderboard');

        Cache::put(
            "dashquiz:user:{$user->id}:status",
            'online',
            now()->addMinutes(5)
        );

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }


    ###############################################
    # REGISTER API
    ###############################################
    public function register(Request $request)
    {
        $valid = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:dasher,email',
            'password' => 'required|string|confirmed|min:6',
        ], [
            'first_name.required' => 'Enter your first name',
            'last_name.required' => 'Enter your last name',

            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email is already in use',

            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
            'password.confirmed' => 'Please confirm your password',
        ]);

        $dasher = Dasher::create([
            'first_name' => $valid['first_name'],
            'last_name' => $valid['last_name'],
            'email' => $valid['email'],
            'password' => Hash::make($valid['password']),
            'role' => 'dasher',
        ]);

        $token = $dasher->createToken('mobile')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Account created successfully',
            'token' => $token,
            'data' => $dasher
        ], 201);
    }
    ###############################################
    # LOGOUT API
    ###############################################
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->update([
                'active_status' => 0,
                'last_activity' => now(),
            ]);

            // delete Sanctum token(s)
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
            'message' => 'Logged out successfully'
        ]);
    }

    public function heartbeat(Request $request)
    {
        $user = $request->user();

        // Not authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        // If scheduler already marked offline → force logout
        if ($user->active_status != 1) {

            $user->tokens()->delete();

            Auth::guard('dasher')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => 'error',
                'message' => 'Session expired'
            ], 403);
        }

        // Throttle DB updates (avoid spam)
        if (!$user->last_activity || $user->last_activity->lt(now()->subSeconds(30))) {
            $user->update([
                'last_activity' => now(),
                'active_status' => 1
            ]);
        }

        return response()->json([
            'status' => 'ok'
        ]);
    }

    ###############################################
    # QUIZ MANAGEMENT APIs
    ###############################################
    public function allQuizzes()
    {
        return response()->json([
            'status' => 'success',
            'data' => Quiz::all()
        ], 200);
    }
    public function createQuiz(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:10',
            'questions.*.text' => 'required|string',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string',
            'questions.*.correct_option' => 'required|integer|min:0|max:3',
        ]);

        DB::beginTransaction();

        try {
            // 1. Create the Quiz
            DB::insert("INSERT INTO quizzes (title, description) VALUES (?, ?)", [
                $request->title,
                $request->description
            ]);

            $quiz_id = DB::getPdo()->lastInsertId();

            // 2. Loop through and create Questions
            foreach ($request->questions as $q) {
                DB::insert("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)", [
                    $quiz_id,
                    $q['text']
                ]);

                $question_id = DB::getPdo()->lastInsertId();
                $correctOptionId = null;

                // 3. Loop through and create Options
                foreach ($q['options'] as $i => $optText) {

                    // Figure out if this specific option is the correct one (1 for true, 0 for false)
                    $isCorrect = ($i == $q['correct_option']) ? 1 : 0;

                    // FIXED: Insert BOTH the text and the is_correct flag into question_options
                    DB::insert("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)", [
                        $question_id,
                        $optText,
                        $isCorrect
                    ]);

                    // (Optional) If you still need data in the answers table for other features, 
                    // you can leave the answers insert here. Otherwise, you can safely delete it!
                    DB::insert("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)", [
                        $question_id,
                        $optText,
                        $isCorrect
                    ]);
                }
            }
            // Log the activity
            $this->logActivity('New Quiz', "Quiz '{$request->title}' was created");

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz created successfully',
                'quiz_id' => $quiz_id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    ###############################################
    # EDIT QUIZ (API Fetch)
    ###############################################
    public function editQuiz(int $id)
    {
        try {

            $quizData = Quiz::where('id', $id)->get();

            //if its empty, then return error
            if (empty($quizData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quiz not found'
                ], 404);
            }

            $quiz = $quizData[0];
            $sql = "SELECT * FROM questions WHERE quiz_id = ?";
            $questions = DB::select($sql, [$id]);

            foreach ($questions as &$q) {
                $sql = "SELECT id, option_text FROM question_options WHERE question_id = ?";
                $q->options = DB::select($sql, [$q->id]);

                $sql = "SELECT id FROM answers WHERE question_id = ? AND is_correct = 1";
                $correct = DB::select($sql, [$q->id]);
                $q->correct_option_id = $correct ? $correct[0]->id : null;
            }

            return response()->json([
                'status' => 'success',
                'quiz' => $quiz,
                'questions' => $questions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server crash: ' . $e->getMessage()
            ], 500);
        }
    }
    public function createNewAdmin(Request $request)
    {

        if ($request->user()->role === 'admin') {
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
                'message' => 'New admin created successfully!'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 403);
    }

    ###############################################
    # UPDATE QUIZ (API Submission)
    ###############################################
    public function updateQuiz(Request $request, int $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array',
            'questions.*.text' => 'required|string',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.correct_option' => 'required|integer|min:0|max:3',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category' => 'nullable|string|max:255',
            'difficulty' => 'nullable|string|in:easy,medium,hard'
        ]);

        try {
            DB::beginTransaction();

            DB::update("UPDATE quizzes SET title = ?, description = ?, category = ?, difficulty = ? WHERE id = ?", [
                $request->title,
                $request->description,
                $request->category,
                $request->difficulty,
                $id
            ]);

            $receivedQuestionIds = [];

            foreach ($request->questions as $qData) {
                if (isset($qData['id'])) {
                    $questionId = $qData['id'];
                    DB::update("UPDATE questions SET question_text = ? WHERE id = ?", [
                        $qData['text'],
                        $questionId
                    ]);
                } else {
                    DB::insert("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)", [
                        $id,
                        $qData['text']
                    ]);
                    $questionId = DB::getPdo()->lastInsertId();
                }

                $receivedQuestionIds[] = $questionId;

                $existingOptions = DB::select("SELECT id FROM question_options WHERE question_id = ? ORDER BY id ASC", [$questionId]);

                DB::delete("DELETE FROM answers WHERE question_id = ?", [$questionId]);

                foreach ($qData['options'] as $index => $optionText) {
                    $isCorrect = ($qData['correct_option'] == $index) ? 1 : 0;

                    if (isset($existingOptions[$index])) {
                        $optionId = $existingOptions[$index]->id;
                        DB::update("UPDATE question_options SET option_text = ?, is_correct = ? WHERE id = ?", [
                            $optionText,
                            $isCorrect,
                            $optionId
                        ]);
                    } else {
                        DB::insert("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)", [
                            $questionId,
                            $optionText,
                            $isCorrect
                        ]);
                        $optionId = DB::getPdo()->lastInsertId();
                    }

                    DB::insert("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)", [
                        $questionId,
                        $optionText,
                        $isCorrect
                    ]);

                    if ($isCorrect == 1) {
                        $correctOptionIdForQuestionTable = $optionId;
                    }
                }
            }
            if (!empty($receivedQuestionIds)) {
                $placeholders = implode(',', array_fill(0, count($receivedQuestionIds), '?'));
                $deleteParams = array_merge([$id], $receivedQuestionIds);
                DB::delete("DELETE FROM questions WHERE quiz_id = ? AND id NOT IN ($placeholders)", $deleteParams);
            }
            // <-- LOG ACTIVITY HERE (Update)
            $this->logActivity('Edit', "Quiz Title '{$request->title}' was updated");

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Quiz updated successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }
    ###############################################
    # DELETE QUIZ
    ###############################################
    public function deleteQuiz(int $id)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found'
            ], 404);
        }
        $quizTitle = $quiz->title; // Save title before deleting for the log
        $quiz->delete();
        // <-- LOG ACTIVITY HERE (Quiz Deletion)
        $this->logActivity('Delete', "Quiz '{$quizTitle}' (ID: $id) was deleted by");
        return response()->json([
            'status' => 'success',
            'message' => "Quiz ID $id deleted"
        ], 200);
    }

    ###############################################
    # USERS TABLE API
    ###############################################
    public function allUsers()
    {
        $users = Dasher::select(
            'id',
            'email',
            'first_name',
            'last_name',
            'profile_photo',
            'created_at',
        )->where('role', 'dasher')->orderBy('created_at', 'desc')->get();

        $users->map(function ($user) {
            //count quizzes taken for each user
            $user->quizzes_taken = QuizRecord::where('user_id', $user->id)->count();
            return $user;
        });

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ], 200);
    }

    public function updateUser(Request $request, int $id)
    {
        $user = Dasher::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:dasher,email,' . $user->id . '|max:255',
            'password' => ['nullable', 'required_with:new_password'],
            'new_password' => ['nullable', 'min:6', 'confirmed'],
        ]);

        // Only run if user wants to change password
        if ($request->filled('new_password')) {

            // Check current password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            $user->password = Hash::make($request->new_password);
        }
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->save();

        $this->logActivity('Update', "Dasher ID #'{$user->id}' was updated");

        return response()->json([
            'message' => 'User updated successfully'
        ]);
    }

    public function deleteUser(int $id)
    {
        $user = Dasher::findOrFail($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // <-- LOG ACTIVITY HERE (User Deletion)
        $this->logActivity('Delete', "User ID {$id} named {$user->first_name} was deleted");
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => "User ID $id deleted"
        ], 200);
    }
    ###############################################
    # STUDENT RECORDS API
    ###############################################
    public function studentRecords()
    {
        if (Auth::guard('dasher')->check()) {
            return response()->json([
                'status' => 'success',
                'data' => QuizRecord::query()
                    ->select([
                        'id',
                        'quiz_id',
                        'user_id',
                        'score',
                        'created_at'
                    ])
                    ->with([
                        'quiz:id,title',
                        'user:id,profile_photo,first_name,last_name'
                    ])
                    ->orderBy('created_at', 'asc')
                    ->get()
            ], 200);
        }
    }
}
