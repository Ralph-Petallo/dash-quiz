<?php

namespace App\Http\Requests\AssessmentRequests;

use Illuminate\Foundation\Http\FormRequest;

class RJ45CompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'standard' => ['required', 'string', 'in:A,B'],
            'passed' => ['required', 'boolean'],
        ];
    }
}
