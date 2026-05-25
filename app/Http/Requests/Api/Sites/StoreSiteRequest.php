<?php

namespace App\Http\Requests\Api\Sites;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreSiteRequest',
    required: ['name', 'url'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Example Site'),
        new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com'),
        new OA\Property(property: 'update_interval', description: 'Interval in seconds', type: 'integer', example: 300),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'project_id', type: 'integer', example: 1, nullable: true),
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
    ],
    type: 'object'
)]
class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('url')) {
            $raw = (string) $this->url;

            // Handle bare domains (e.g. "example.com") so parse_url can identify the host.
            if (! str_contains($raw, '://')) {
                $raw = 'https://'.$raw;
            }

            $parsed = parse_url($raw);

            if ($parsed && isset($parsed['host'])) {
                $scheme = $parsed['scheme'] ?? 'https';
                $host = $parsed['host'];
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                $path = ltrim($parsed['path'] ?? '', '/');

                if ($path !== '') {
                    // Preserve the path so the path-rejection rule in rules() can surface
                    // ERROR_URL_PATH_NOT_SUPPORTED instead of silently stripping it and
                    // producing a misleading ERROR_URL_TAKEN from the unique constraint.
                    $this->merge(['url' => "{$scheme}://{$host}{$port}/{$path}"]);
                } else {
                    $this->merge(['url' => "{$scheme}://{$host}{$port}"]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
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
            'url' => [
                'bail',
                'required',
                'url',
                'max:255',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (ltrim(parse_url($value, PHP_URL_PATH) ?? '', '/') !== '') {
                        $fail('ERROR_URL_PATH_NOT_SUPPORTED');
                    }
                },
                'unique:sites,url',
            ],
            'project_id' => [
                'sometimes',
                'nullable',
                Rule::exists('projects', 'id')->where('user_id', $this->user()?->id),
            ],
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
