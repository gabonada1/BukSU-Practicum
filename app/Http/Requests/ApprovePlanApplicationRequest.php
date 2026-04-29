<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePlanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subdomain' => ['nullable', 'alpha_dash', 'max:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subscription_starts_at' => ['required', 'date'],
            'subscription_expires_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'bandwidth_limit_gb' => ['required', 'numeric', 'min:1', 'max:100000'],
            'bandwidth_used_gb' => ['nullable', 'numeric', 'min:0', 'lte:bandwidth_limit_gb'],
            'admin_password' => ['required', 'string', 'min:8'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
