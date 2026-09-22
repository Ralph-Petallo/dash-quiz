<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        if (empty($answers)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No answers provided',
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

            // Get the question item to find the correct answer
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

            // Check if the answer matches the question's ID (correct answer)
            $isCorrect = $answerId == $question->id;

            if ($isCorrect) {
                $correctCount++;
            }

            $results[] = [
                'question_id' => $questionId,
                'answer_id' => $answerId,
                'correct' => $isCorrect,
            ];
        }

        return response()->json([
            'status' => 'success',
            'total' => count($results),
            'correct' => $correctCount,
            'incorrect' => count($results) - $correctCount,
            'results' => $results,
        ]);
    }
}