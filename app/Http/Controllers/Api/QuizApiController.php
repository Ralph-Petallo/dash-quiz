<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use App\Models\QuizRecord;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{

    ###############################################
    # UPDATE QUIZ (API Submission)
    ###############################################
    public function updateQuiz(Request $request, int $id)
    {
        // Validate quiz and question data
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
            // Start database transaction
            DB::beginTransaction();

            // Update quiz details
            DB::update("UPDATE quizzes SET title = ?, description = ?, category = ?, difficulty = ? WHERE id = ?", [
                $request->title,
                $request->description,
                $request->category,
                $request->difficulty,
                $id
            ]);

            // Track submitted question IDs
            $receivedQuestionIds = [];

            foreach ($request->questions as $qData) {

                // Update existing question or create a new one
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

                // Get existing options
                $existingOptions = DB::select(
                    "SELECT id FROM question_options WHERE question_id = ? ORDER BY id ASC",
                    [$questionId]
                );

                // Remove old answer records
                DB::delete("DELETE FROM answers WHERE question_id = ?", [$questionId]);

                foreach ($qData['options'] as $index => $optionText) {

                    // Mark the correct option
                    $isCorrect = ($qData['correct_option'] == $index) ? 1 : 0;

                    // Update existing option or create a new one
                    if (isset($existingOptions[$index])) {
                        $optionId = $existingOptions[$index]->id;

                        DB::update(
                            "UPDATE question_options SET option_text = ?, is_correct = ? WHERE id = ?",
                            [$optionText, $isCorrect, $optionId]
                        );
                    } else {
                        DB::insert(
                            "INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)",
                            [$questionId, $optionText, $isCorrect]
                        );

                        $optionId = DB::getPdo()->lastInsertId();
                    }

                    // Recreate answer record
                    DB::insert(
                        "INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)",
                        [$questionId, $optionText, $isCorrect]
                    );

                    // Store correct option ID
                    if ($isCorrect == 1) {
                        $correctOptionIdForQuestionTable = $optionId;
                    }
                }
            }

            // Delete questions removed from the quiz
            if (!empty($receivedQuestionIds)) {
                $placeholders = implode(',', array_fill(0, count($receivedQuestionIds), '?'));
                $deleteParams = array_merge([$id], $receivedQuestionIds);

                DB::delete(
                    "DELETE FROM questions WHERE quiz_id = ? AND id NOT IN ($placeholders)",
                    $deleteParams
                );
            }

            // Log quiz update
            $this->logActivity('Edit', "Quiz Title '{$request->title}' was updated");

            // Save all changes
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz updated successfully!'
            ], 200);
        } catch (\Exception $e) {

            // Undo changes if an error occurs
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update: ' . $e->getMessage()
            ], 500);
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
    # QUIZ MANAGEMENT APIs
    ###############################################
    public function allQuizzes()
    {
        return response()->json([
            'status' => 'success',
            'data' => Quiz::all()
        ], 200);
    }
    /*
    |--------------------------------------------------------------------------
    | GET QUIZ (RANDOM QUESTIONS)
    |--------------------------------------------------------------------------
    */

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

    public function getQuiz(int $id)
    {
        $quiz = Quiz::with('questions.options')->findOrFail($id);

        $questions = $quiz->questions
            ->filter(fn($q) => $q->options->count() > 0)
            ->shuffle()
            ->take(10)
            ->values();

        $optionLabels = ['A', 'B', 'C', 'D'];

        return response()->json([
            'status' => 'success',
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_questions' => $questions->count(),

                'questions' => $questions->map(function ($q, $index) use ($optionLabels) {

                    $options = $q->options->shuffle()->values();

                    return [
                        'id' => $q->id,
                        'text' => $q->question_text,
                        'question_number' => $index + 1,

                        'options' => $options->map(function ($opt, $optIndex) use ($optionLabels) {
                            return [
                                'id' => $opt->id,
                                'text' => $opt->option_text,
                                'label' => $optionLabels[$optIndex] ?? chr(65 + $optIndex),
                            ];
                        })->values(),
                    ];
                })->values(),
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK ANSWER (REALTIME)
    |--------------------------------------------------------------------------
    */
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'required|exists:question_options,id',
        ]);

        $question = Question::with('options')->findOrFail($validated['question_id']);

        $selected = $question->options->firstWhere('id', $validated['answer_id']);
        $correct = $question->options->firstWhere('is_correct', true);

        $isCorrect = $selected && $correct && $selected->id === $correct->id;

        return response()->json([
            'status' => 'success',
            'correct' => $isCorrect
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE QUIZ RESULT + ATTEMPTS (FIXED)
    |--------------------------------------------------------------------------
    */
    public function submitQuizResult(Request $request)
    {
        // FIX #2: Proper nested validation for answers array
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'elapsed_time' => 'required|integer|min:0',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_id' => 'required|exists:question_options,id',
        ]);

        // FIX #1: Consistent auth usage
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        /*
        |---------------------------------------
        | 1. CREATE QUIZ RECORD
        |---------------------------------------
        */
        $record = QuizRecord::create([
            'user_id' => $user->id,
            'quiz_id' => $validated['quiz_id'],
            'score' => $validated['score'],
            'elapsed_time' => $validated['elapsed_time'],
        ]);

        /*
        |---------------------------------------
        | 2. SAVE EACH QUESTION ATTEMPT
        |---------------------------------------
        */
        foreach ($validated['answers'] as $answer) {

            $question = Question::with('options')->find($answer['question_id']);

            if (!$question) continue;

            $selected = $question->options->firstWhere('id', $answer['answer_id']);
            $correct = $question->options->firstWhere('is_correct', true);

            QuizAttempt::create([
                'quiz_record_id' => $record->id,
                'question_id' => $question->id,
                'selected_option_id' => $selected?->id,

                // is_correct will be remove (it has no use)
                'is_correct' => $selected && $correct
                    ? $selected->id === $correct->id
                    : false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'record_id' => $record->id,
            'score' => $record->score,
            'elapsed_time' => $record->elapsed_time,
            'total_questions' => count($validated['answers']),
            'message' => 'Quiz result saved successfully'
        ]);
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

    /*
    |--------------------------------------------------------------------------
    | GET QUIZ RESULT (REVIEW PAGE)
    |--------------------------------------------------------------------------
    */
    public function getQuizResult(int $id)
    {
        $record = QuizRecord::with([
            'quiz',
            'attempts.question.options',
            'attempts.selectedOption'
        ])->where('id', $id)
            ->where('user_id', auth('sanctum')->user()?->id)
            ->firstOrFail();

        $questions = $record->attempts->map(function ($attempt) {
            $correct = $attempt->question->options->firstWhere('is_correct', true);

            return [
                'question_id' => $attempt->question->id,
                'question' => $attempt->question->question_text,
                'user_answer' => $attempt->selectedOption?->option_text ?? 'No answer',
                'correct_answer' => $correct?->option_text,
                'is_correct' => (bool) $attempt->is_correct,
            ];
        });

        return response()->json([
            'status' => 'success',
            'record_id' => $record->id,
            'quiz_id' => $record->quiz_id,
            'quiz_title' => $record->quiz->title,
            'score' => $record->score,
            'elapsed_time' => $record->elapsed_time,
            'total_questions' => $record->attempts->count(),
            'questions' => $questions
        ]);
    }
}
