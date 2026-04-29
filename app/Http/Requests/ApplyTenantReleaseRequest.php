<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyTenantReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'release_id' => ['required', 'integer', 'exists:central.system_releases,id'],
        ];
    }
}
