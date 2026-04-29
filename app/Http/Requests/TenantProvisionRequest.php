<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantProvisionRequest extends FormRequest
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
            'bandwidth_limit_gb' => ['required', 'numeric', 'min:1', 'max:100000'],
            'bandwidth_used_gb' => ['nullable', 'numeric', 'min:0', 'lte:bandwidth_limit_gb'],
            'subdomain' => ['required', 'alpha_dash', 'max:63'],
            'domain' => ['nullable', 'string', 'max:255'],
            'database' => ['required', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('central.tenants', 'database')],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
