<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizRecord;
use App\Models\ImageIdentificationItem;
use Illuminate\Http\Request;

class ImageIdentificationAssessmentController extends Controller
{
    public function show(int $id)
    {
        $items = ImageIdentificationItem::where('quiz_id', $id)
            ->inRandomOrder()
            ->take(10)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image identification assessment not found',
            ], 404);
        }

        $questions = $items->map(function ($item) use ($id) {
            return [
                'id' => $item->id,
                'question_text' => $item->question_text,
                'image_path' => $item->image_path,
                'item_name'=> $item->item_name,
                'options' => $this->generateOptions($id, $item),
            ];
        });

        return response()->json([
            'status' => 'success',
            'questions' => $questions->values(),
        ]);
    }

    /**
     * Generate 4 randomized answer options.
     *
     * 1 correct description
     * 3 incorrect descriptions
     *
     * The correct answer is NOT identified in the response.
     */
    private function generateOptions(int $quizId, ImageIdentificationItem $item)
    {
        // Get 3 random wrong descriptions
        $wrongOptions = ImageIdentificationItem::where('quiz_id', $quizId)
            ->where('id', '!=', $item->id)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->inRandomOrder()
            ->take(3)
            ->get(['id', 'description']);

        // Add the correct description
        $options = $wrongOptions->push(
            (object) [
                'id' => $item->id,
                'description' => $item->description,
            ]
        );

        // Randomize the position of all 4 options
        return $options
            ->shuffle()
            ->values()
            ->map(function ($option) {
                return [
                    'id' => $option->id,
                    'description' => $option->description,
                ];
            });
    }

    /**
     * Validate batch answers and return results
     */
    public function answer(Request $request)
    {
        $answers = $request->input('answers', []);
        $quizId = $request->input('quiz_id');

        if (empty($answers)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No answers provided',
            ], 400);
        }

        if (!$quizId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Assessment ID is required',
            ], 400);
        }

        $results = [];
        $correctCount = 0;

        foreach ($answers as $answer) {

            $questionId = $answer['question_id'] ?? null;
            $answerId = $answer['answer_id'] ?? null;

            if (!$questionId || !$answerId) {
                continue;
            }

            $question = ImageIdentificationItem::find($questionId);

            if (!$question) {
                $results[] = [
                    'question_id' => $questionId,
                    'answer_id' => $answerId,
                    'correct' => false,
                    'message' => 'Question not found',
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Correct answer
            |--------------------------------------------------------------------------
            |
            | The correct option ID is the same as the image item's ID.
            |
            */

            $isCorrect = (int) $answerId === (int) $question->id;

            if ($isCorrect) {
                $correctCount++;
            }

            $results[] = [
                'question_id' => $questionId,
                'answer_id' => $answerId,
                'correct' => $isCorrect,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SCORE
        |--------------------------------------------------------------------------
        */

        $totalQuestions = count($results);

        if ($totalQuestions === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid answers were submitted',
            ], 400);
        }

        $percentage = round(
            ($correctCount / $totalQuestions) * 100,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | PASSING SCORE
        |--------------------------------------------------------------------------
        */

        $passingScore = 75;

        $status = $percentage >= $passingScore
            ? 'Passed'
            : 'Failed';


        /*
        |--------------------------------------------------------------------------
        | CREATE RECORD
        |--------------------------------------------------------------------------
        */

        $record = QuizRecord::create([

            'user_id' => auth()->id(),

            'quiz_id' => $quizId,

            // Number of correct answers
            'score' => $correctCount,

            'correct_answers' => $correctCount,

            'total_questions' => $totalQuestions,

            'percentage' => $percentage,

            'passing_score' => $passingScore,

            'status' => $status,

            'attempt_count' => 1,

            'quiz_type' => 'image_identification',

            'category' => 'Hardware',

            'completed_at' => now(),

        ]);


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',

            'record_id' => $record->id,

            'score' => $correctCount,

            'correct' => $correctCount,

            'incorrect' => $totalQuestions - $correctCount,

            'total' => $totalQuestions,

            'percentage' => $percentage,

            'passing_score' => $passingScore,

            'result_status' => $status,

            'results' => $results,
        ]);
    }
}