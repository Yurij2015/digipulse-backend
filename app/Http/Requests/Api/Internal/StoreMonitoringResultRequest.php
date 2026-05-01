<?php

namespace App\Http\Requests\Api\Internal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonitoringResultRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'configuration_id' => ['required', 'exists:site_check_configurations,id'],
            'status' => ['required', 'string', 'in:up,down,slow'],
            'response_time_ms' => ['nullable', 'integer'],
            'error_message' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
