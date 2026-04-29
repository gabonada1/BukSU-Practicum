<?php

namespace App\Http\Requests;

use App\Support\Billing\PlanCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'college_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'admin_email' => ['required', 'email', 'max:255'],
            'selected_plan' => ['required', Rule::in(array_keys(PlanCatalog::all()))],
            'preferred_subdomain' => ['nullable', 'alpha_dash', 'max:100'],
            'preferred_domain' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
