<?php

namespace App\Http\Requests\AssessmentRequests;

use Illuminate\Foundation\Http\FormRequest;

class DragDropAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'exists:drag_drop_items,id'],
        ];
    }
}
