<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OjtSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_ojt_hours' => ['required', 'numeric', 'min:1', 'max:9999'],
            'allow_student_hour_override' => ['nullable', 'boolean'],
            'ojt_hours_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
