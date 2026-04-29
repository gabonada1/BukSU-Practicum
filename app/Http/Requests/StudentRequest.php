<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $isUpdate = filled($student);

        return array_filter([
            'student_number' => ['required', 'string', 'max:255', Rule::unique('tenant.tenant_users', 'student_number')->ignore($student?->getKey())],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($student?->getKey())],
            'password' => ['nullable', 'string', 'min:8'],
            'program' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'exists:tenant.courses,id'],
            'required_hours' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'completed_hours' => $isUpdate ? ['required', 'numeric', 'min:0'] : null,
            'status' => ['required', Rule::in(['pending', 'accepted', 'deployed', 'completed'])],
            'partner_company_id' => ['nullable', 'integer', 'exists:tenant.partner_companies,id'],
            'is_active' => $isUpdate ? ['required', 'boolean'] : null,
            'email_verified' => $isUpdate ? ['nullable', 'boolean'] : null,
        ]);
    }
}
