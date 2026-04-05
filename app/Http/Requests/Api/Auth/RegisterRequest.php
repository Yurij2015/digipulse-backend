<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    type: 'object',
    required: ['name', 'email', 'first_name', 'last_name', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'johndou'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'johndou@gmail.pro'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Dou'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'StrongPass123!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'StrongPass123!'),
    ]
)]
class RegisterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9._\-]*[a-zA-Z0-9])?$/',
                'unique:users,name',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users',
            ],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(10),
                function ($attribute, $value, $fail) {
                    if (count(array_unique(str_split($value))) < 6) {
                        $fail('The :attribute must contain at least 6 unique characters.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Username can only contain Latin letters, numbers, dots, dashes, and underscores.',
            'name.unique' => 'This username is already taken.',
        ];
    }
}
