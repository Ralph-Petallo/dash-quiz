<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentRequests\MultipleChoiceAnswerRequest;
use App\Http\Requests\AssessmentRequests\MultipleChoiceResultRequest;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;

class MultipleChoiceAssessmentController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $quiz = Quiz::with('questions.options')->findOrFail($id);
        return app(QuizApiController::class)->getQuiz($quiz->id);
    }

    public function answer(MultipleChoiceAnswerRequest $request): JsonResponse
    {
        return app(QuizApiController::class)->submitAnswer($request);
    }

    public function result(MultipleChoiceResultRequest $request): JsonResponse
    {
        return app(QuizApiController::class)->MultipleChoiceResult($request);
    }
}
