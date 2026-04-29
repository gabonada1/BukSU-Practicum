<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in(['basic', 'pro', 'premium'])],
            'subscription_starts_at' => ['required', 'date'],
            'subscription_expires_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'is_active' => ['required', Rule::in(['0', '1'])],
            'bandwidth_limit_gb' => ['required', 'numeric', 'min:1', 'max:100000'],
            'bandwidth_used_gb' => ['nullable', 'numeric', 'min:0'],
            'domain_hosts' => ['nullable', 'string', 'max:2000'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
        ];
    }
}
