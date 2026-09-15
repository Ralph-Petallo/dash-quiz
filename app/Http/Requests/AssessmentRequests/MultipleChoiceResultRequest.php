<?php

namespace App\Http\Requests\AssessmentRequests;

use Illuminate\Foundation\Http\FormRequest;

class MultipleChoiceResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'score' => ['required', 'integer', 'min:0'],
            'elapsed_time' => ['required', 'integer', 'min:0'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.answer_id' => ['required', 'integer', 'exists:question_options,id'],
        ];
    }
}
