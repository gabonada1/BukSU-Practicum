<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'portal_title' => ['required', 'string', 'max:120'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_alt_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_soft_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_alt_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'border_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'portal_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
