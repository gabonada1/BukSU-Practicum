<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'available_positions' => ['nullable', 'array'],
            'available_positions.*' => ['string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'intern_slot_limit' => ['required', 'integer', 'min:1'],
        ];
    }

    public function payload(): array
    {
        $validated = $this->validated();

        $validated['available_positions'] = collect($validated['available_positions'] ?? [])
            ->map(fn ($position) => trim((string) $position))
            ->filter()
            ->unique()
            ->implode(PHP_EOL);

        $validated['required_documents'] = null;

        return $validated;
    }
}
