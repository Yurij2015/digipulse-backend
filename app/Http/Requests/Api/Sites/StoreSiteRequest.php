<?php

namespace App\Http\Requests\Api\Sites;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'update_interval' => ['sometimes', 'integer', 'min:60', 'max:86400'],
            'is_active' => ['sometimes', 'boolean'],
            'checks' => ['sometimes', 'array'],
            'checks.*.check_type_id' => ['required', 'exists:check_types,id'],
            'checks.*.params' => ['sometimes', 'array'],
        ];
    }
}
