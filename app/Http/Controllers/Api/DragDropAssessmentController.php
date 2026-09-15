<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentRequests\DragDropAnswerRequest;

class DragDropAssessmentController extends Controller
{
    public function show(int $id)
    {
        return app(DragDropController::class)->getDragDropQuiz($id);
    }

    public function answer(DragDropAnswerRequest $request)
    {
        return app(DragDropController::class)->submitDragDropAnswer($request);
    }
}
