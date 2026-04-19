<?php

namespace App\Http\Requests\Api\Sites;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateSiteRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Example Site Updated'),
        new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example-updated.com'),
        new OA\Property(property: 'update_interval', type: 'integer', example: 300, description: 'Interval in seconds'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'checks',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Existing configuration ID to update, if provided'),
                    new OA\Property(property: 'check_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'params', type: 'object', example: ['keyword' => 'test'], nullable: true),
                ],
                type: 'object'
            )
        ),
    ]
)]
class UpdateSiteRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:255', Rule::unique('sites')->ignore($this->route('site'))],
            'update_interval' => ['sometimes', 'integer', 'min:60', 'max:86400'],
            'is_active' => ['sometimes', 'boolean'],
            'checks' => ['sometimes', 'array'],
            'checks.*.id' => ['sometimes', 'integer', 'exists:site_check_configurations,id'],
            'checks.*.check_type_id' => ['required_without:checks.*.id', 'exists:check_types,id'],
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
