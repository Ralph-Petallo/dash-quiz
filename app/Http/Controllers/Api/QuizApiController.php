<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizRecord;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET QUIZ (RANDOM QUESTIONS)
    |--------------------------------------------------------------------------
    */
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
    | SAVE QUIZ RESULT + ATTEMPTS (MAIN FIX)
    |--------------------------------------------------------------------------
    */
    public function submitQuizResult(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'elapsed_time' => 'required|integer|min:0',
            'answers' => 'required|array'
        ]);

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
                'is_correct' => $selected && $correct
                    ? $selected->id === $correct->id
                    : false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'record_id' => $record->id,
            'record' => $record
        ]);
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
        ])
            ->where('id', $id)
            ->where('user_id', auth('sanctum')->id())
            ->firstOrFail();

        $questions = $record->attempts->map(function ($attempt) {

            $correct = $attempt->question->options
                ->firstWhere('is_correct', true);

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
            'score' => $record->score,
            'elapsed_time' => $record->elapsed_time,
            'total_questions' => $record->attempts->count(),
            'questions' => $questions
        ]);
    }
}
