<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InternshipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'partner_company_id' => ['required', 'integer', 'exists:tenant.partner_companies,id'],
            'position_applied' => ['required', 'string', 'max:255'],
            'student_notes' => ['nullable', 'string', 'max:1500'],
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected', 'deployed'])],
            'admin_feedback' => ['nullable', 'string', 'max:1500'],
            'resume' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'endorsement_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'moa' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }
}
