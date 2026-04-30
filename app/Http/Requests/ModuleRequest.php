<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $module = $this->route('module');

        return [
            'name' => ['required', 'string', 'max:255'],
            'plan_id' => ['required', 'numeric', 'min:1'],
        ];
    }
}
