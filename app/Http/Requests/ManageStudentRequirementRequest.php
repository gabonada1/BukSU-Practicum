<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageStudentRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'requirement_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['submitted', 'approved', 'revision', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:1500'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }
}
