<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Quiz;

class AssessmentTypeController extends Controller
{
    public function getAssessmentTypes(int $id)
    {
        // Get the selected quiz
        $quiz = Quiz::findOrFail($id);

        // Get assessments for the quiz's COC
        $assessments = Assessment::where('coc_id', $quiz->coc_number)->get();

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
            'assessments' => $assessments,
        ]);
    }
}