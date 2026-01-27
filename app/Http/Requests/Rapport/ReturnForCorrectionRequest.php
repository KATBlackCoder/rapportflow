<?php

namespace App\Http\Requests\Rapport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnForCorrectionRequest extends FormRequest
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
            'correction_reason' => ['required', 'string', 'max:1000'],
            'response_ids' => ['required', 'array'],
            'response_ids.*' => ['required', Rule::exists('questionnaire_responses', 'id')],
        ];
    }
}
