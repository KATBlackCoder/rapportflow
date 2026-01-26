<?php

namespace App\Http\Requests\Questionnaire;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['published', 'archived'])],
            'target_type' => ['required', Rule::in(['employees', 'supervisors'])],
            'questions' => ['nullable', 'array'],
            'questions.*.type' => ['required', Rule::in(['text', 'textarea', 'radio', 'checkbox', 'select', 'number', 'date', 'email'])],
            'questions.*.question' => ['required', 'string'],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.conditional_question_index' => ['nullable', 'integer', 'min:0'],
            'questions.*.conditional_question_id' => ['nullable'],
            'questions.*.conditional_value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
