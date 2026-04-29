<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:40', 'in:account,billing,technical,data,updates,general'],
            'priority' => ['required', 'string', 'max:20', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
