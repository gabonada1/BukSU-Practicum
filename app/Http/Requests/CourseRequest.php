<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('tenant.courses', 'code')->ignore($course?->getKey())],
            'name' => ['required', 'string', 'max:255'],
            'required_ojt_hours' => ['required', 'numeric', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
