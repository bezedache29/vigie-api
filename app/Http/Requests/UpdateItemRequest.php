<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
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
            // 'summarized' et 'error' sont gérés par le pipeline de résumé,
            // seule bascule manuelle possible : ignorer un item ou le remettre en attente.
            'status' => ['required', 'in:pending,ignored'],
        ];
    }
}
