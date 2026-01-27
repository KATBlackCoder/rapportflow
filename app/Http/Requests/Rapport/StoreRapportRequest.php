<?php

namespace App\Http\Requests\Rapport;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRapportRequest extends FormRequest
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
        $rules = [
            'questionnaire_id' => ['required', Rule::exists('questionnaires', 'id')],
            'responses' => ['required', 'array'],
            'responses.*.question_id' => ['required', Rule::exists('questions', 'id')],
            'responses.*.row_identifier' => ['nullable', 'string', 'max:255'],
            'responses.*.response' => ['required'],
        ];

        // Validation dynamique selon le type de question
        if ($this->has('responses')) {
            foreach ($this->input('responses', []) as $index => $response) {
                if (isset($response['question_id'])) {
                    $question = Question::find($response['question_id']);
                    if ($question) {
                        $responseRules = $this->getResponseValidationRules($question, $index);
                        $rules = array_merge($rules, $responseRules);
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get validation rules for a specific question response.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function getResponseValidationRules(Question $question, int $index): array
    {
        $rules = [];

        switch ($question->type->value) {
            case 'text':
            case 'textarea':
            case 'email':
                $rules["responses.{$index}.response"] = ['required', 'string'];
                if ($question->type->value === 'email') {
                    $rules["responses.{$index}.response"][] = 'email';
                }
                break;

            case 'number':
                $rules["responses.{$index}.response"] = ['required', 'numeric'];
                break;

            case 'date':
                $rules["responses.{$index}.response"] = ['required', 'date'];
                break;

            case 'select':
            case 'radio':
                $options = $question->options ?? [];
                $validOptions = array_keys($options);
                $rules["responses.{$index}.response"] = [
                    'required',
                    Rule::in($validOptions),
                ];
                break;

            case 'checkbox':
            case 'selectmulti':
                $options = $question->options ?? [];
                $validOptions = array_keys($options);
                $rules["responses.{$index}.response"] = [
                    'required',
                    'array',
                ];
                $rules["responses.{$index}.response.*"] = [
                    Rule::in($validOptions),
                ];
                break;
        }

        return $rules;
    }
}
