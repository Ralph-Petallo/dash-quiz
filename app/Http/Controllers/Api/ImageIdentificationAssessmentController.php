<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;

class ImageIdentificationAssessmentController extends Controller
{
    public function show(int $id)
    {
        $questions = Question::where('quiz_id', $id)
            ->where('question_type', 'image_identification')
            ->with('options')
            ->inRandomOrder()
            ->take(10)
            ->get()
            ->each(function ($question) {
                $question->image_path = asset('storage/images/images_identification' . $question->image_path);
            });


        if ($questions->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image identification assessment not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'questions' => $questions,
        ]);
    }
}
