<?php

namespace App\Http\Requests\AssessmentRequests;

use Illuminate\Foundation\Http\FormRequest;

class MultipleChoiceAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions_array' => ['required', 'integer', 'exists:questions,id'],
            'answers_array' => ['required', 'integer', 'array', 'exists:question_options,id'],
        ];
    }
}
