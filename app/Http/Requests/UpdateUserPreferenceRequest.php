<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keywords' => ['sometimes', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'digest_frequency' => ['sometimes', 'in:daily,weekly'],
            'source_ids' => ['sometimes', 'array'],
            'source_ids.*' => ['integer', 'exists:sources,id'],
        ];
    }
}
