<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentRequests\RJ45CompletionRequest;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;

class RJ45AssessmentController extends Controller
{
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'quiz' => Quiz::findOrFail($id),
        ]);
    }


    public function complete(RJ45CompletionRequest $request): JsonResponse
    {
        if (!$request->boolean('passed')) {
            return response()->json(['status' => 'error', 'message' => 'The cable did not pass continuity testing.'], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'RJ-45 assessment completed.']);
    }
}
