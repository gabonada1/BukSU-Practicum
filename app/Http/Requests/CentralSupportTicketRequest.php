<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CentralSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['open', 'in_progress', 'waiting_on_tenant', 'resolved', 'closed'])],
            'superadmin_response' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
