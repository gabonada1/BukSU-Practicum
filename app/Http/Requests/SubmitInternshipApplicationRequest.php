<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInternshipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_company_id' => ['required', 'integer', 'exists:tenant.partner_companies,id'],
            'position_applied' => ['required', 'string', 'max:255'],
            'student_notes' => ['nullable', 'string', 'max:1500'],
            'resume' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'endorsement_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'moa' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }
}
