<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\DragDrop;

class DragDropController extends Controller
{
    public function getDragDropQuiz()
    {
        $question = Question::where('question_type', 'drag_drop')->get()->toArray();


        if (!$question) {
            return response()->json([
                'message' => 'Drag & Drop question not found'
            ], 404);
        }

        $items = DragDrop::where('question_id', $question[0]['id'])
            ->get([
                'id',
                'item_text',
                'item_image_path',
                'question_id'
            ]);

        return response()->json([
            'question' => $question,
            'items' => $items
        ]);
    }

    public function submitDragDropAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'answers' => 'required|array|min:1',
            'answers.*' => 'required|integer|exists:drag_drop_items,id',
        ]);

        try {

            /*
        |--------------------------------------------------------------------------
        | Get the question
        |--------------------------------------------------------------------------
        */

            $question = Question::findOrFail(
                $request->question_id
            );


            /*
        |--------------------------------------------------------------------------
        | Get the correct order
        |--------------------------------------------------------------------------
        |
        | The database contains:
        |
        | item ID | correct_position
        |    1    |       1
        |    2    |       2
        |    3    |       3
        |    4    |       4
        |
        */

            $correctItems = DragDrop::where(
                'question_id',
                $question->id
            )
                ->orderBy('correct_position')
                ->get();


            /*
        |--------------------------------------------------------------------------
        | Student's submitted order
        |--------------------------------------------------------------------------
        */

            $submittedAnswers = $request->answers;


            /*
        |--------------------------------------------------------------------------
        | Compare answers
        |--------------------------------------------------------------------------
        */

            $correctOrder = $correctItems
                ->pluck('id')
                ->values()
                ->toArray();


            $score = 0;
            $total = count($correctOrder);


            foreach ($submittedAnswers as $index => $itemId) {

                if (
                    isset($correctOrder[$index]) &&
                    $correctOrder[$index] == $itemId
                ) {
                    $score++;
                }
            }


            /*
        |--------------------------------------------------------------------------
        | Check if the entire arrangement is correct
        |--------------------------------------------------------------------------
        */

            $isCorrect = $score === $total;


            /*
        |--------------------------------------------------------------------------
        | Return result
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'status' => 'success',
                'score' => $score,
                'total' => $total,
                'is_correct' => $isCorrect,
                'submitted_order' => $submittedAnswers,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit answer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
