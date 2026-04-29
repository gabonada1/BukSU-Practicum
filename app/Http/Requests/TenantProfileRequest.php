<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TenantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        [$role, $user] = $this->profileContext();

        return match ($role) {
            'student' => [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user?->getKey())],
                'program' => ['nullable', 'string', 'max:255'],
            ],
            'supervisor' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user?->getKey())],
                'position' => ['nullable', 'string', 'max:255'],
            ],
            default => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user?->getKey())],
            ],
        };
    }

    protected function profileContext(): array
    {
        if ($this->routeIs('tenant*.admin.*')) {
            return ['admin', Auth::guard('tenant_admin')->user()];
        }

        if ($this->routeIs('tenant*.supervisor.*')) {
            return ['supervisor', Auth::guard('supervisor')->user()];
        }

        if ($this->routeIs('tenant*.student.*')) {
            return ['student', Auth::guard('student')->user()];
        }

        return ['admin', Auth::guard('tenant_admin')->user()];
    }
}
