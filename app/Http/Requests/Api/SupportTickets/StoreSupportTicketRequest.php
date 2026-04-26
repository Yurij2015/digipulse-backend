<?php

namespace App\Http\Requests\Api\SupportTickets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'contact_email' => ['required_without:user_id', 'nullable', 'email'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ];
    }
}
