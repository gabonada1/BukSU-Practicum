<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supervisor = $this->route('supervisor');
        $isUpdate = filled($supervisor);

        return array_filter([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($supervisor?->getKey())],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'partner_company_id' => ['nullable', 'integer', 'exists:tenant.partner_companies,id'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => $isUpdate ? ['required', 'boolean'] : null,
            'email_verified' => $isUpdate ? ['nullable', 'boolean'] : null,
        ]);
    }
}
