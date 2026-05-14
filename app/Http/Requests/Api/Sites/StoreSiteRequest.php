<?php

namespace App\Http\Requests\Api\Sites;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreSiteRequest',
    type: 'object',
    required: ['name', 'url'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Example Site'),
        new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com'),
        new OA\Property(property: 'update_interval', type: 'integer', example: 300, description: 'Interval in seconds'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'checks',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'check_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'params', type: 'object', example: ['keyword' => 'test'], nullable: true),
                ],
                type: 'object'
            )
        ),
    ]
)]
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
            'url' => ['required', 'url', 'max:255', 'unique:sites,url'],
            'update_interval' => ['sometimes', 'integer', 'min:60', 'max:86400'],
            'is_active' => ['sometimes', 'boolean'],
            'checks' => ['sometimes', 'array'],
            'checks.*.check_type_id' => ['required', 'exists:check_types,id'],
            'checks.*.params' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.unique' => 'ERROR_URL_TAKEN',
        ];
    }
}
