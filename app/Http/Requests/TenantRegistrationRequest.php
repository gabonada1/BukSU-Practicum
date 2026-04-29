<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->input('role');

        $rules = [
            'role' => ['required', 'in:student,teacher'],
        ];

        if ($role === 'student') {
            return $rules + [
                'student_number' => ['required', 'string', 'max:255', 'unique:tenant.tenant_users,student_number'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:tenant.tenant_users,email'],
                'course_id' => ['nullable', 'exists:tenant.courses,id'],
                'program' => ['nullable', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        if ($role === 'teacher') {
            return $rules + [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:tenant.tenant_users,email'],
                'partner_company_id' => ['required', 'integer', 'exists:tenant.partner_companies,id'],
                'department' => ['required', 'string', 'max:255'],
                'position' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        return $rules;
    }
}
