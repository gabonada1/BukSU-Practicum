@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
    $mode = $mode ?? 'create';
    $isEditing = $mode === 'edit' && filled($editingCompany ?? null);
    $company = $editingCompany ?? null;
    $positionOptions = [
        'Accounting Intern',
        'Administrative Aide Intern',
        'Business Operations Intern',
        'Customer Service Intern',
        'Data Analyst Intern',
        'Database Administrator Intern',
        'Graphic Design Intern',
        'Human Resources Intern',
        'IT Support Intern',
        'Laboratory Assistant',
        'Marketing Intern',
        'Multimedia Intern',
        'Network Support Intern',
        'Office Assistant Intern',
        'Project Management Intern',
        'QA Tester Intern',
        'Research Assistant',
        'Software Developer Intern',
        'Systems Analyst Intern',
        'Technical Support Intern',
        'UI/UX Design Intern',
        'Web Developer Intern',
    ];
    $selectedPositions = collect(old('available_positions', $company ? $company->availablePositionsList() : []))
        ->map(fn ($position) => trim((string) $position))
        ->filter()
        ->values()
        ->all();
    $action = $isEditing
        ? route('tenant.admin.companies.update', ['company' => $company])
        : $formActions['companies'];
@endphp

@unless ($embedded)
<article >
@endunless
    @if ($showHeading)
        <h2>{{ $isEditing ? 'Edit Partner Organization' : 'New Partner Organization' }}</h2>
    @endif
    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($isEditing)
            @method('PATCH')
        @endif
        <label>Organization Name <input type="text" name="name" value="{{ old('name', $company?->name) }}" required></label>
        <label>Industry / Type <input type="text" name="industry" value="{{ old('industry', $company?->industry) }}"></label>
        <label>
            Available Positions
            <select name="available_positions[]" multiple size="8">
                @foreach ($positionOptions as $positionOption)
                    <option value="{{ $positionOption }}" @selected(in_array($positionOption, $selectedPositions, true))>{{ $positionOption }}</option>
                @endforeach
                @foreach ($selectedPositions as $selectedPosition)
                    @if (! in_array($selectedPosition, $positionOptions, true))
                        <option value="{{ $selectedPosition }}" selected>{{ $selectedPosition }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label>Address <input type="text" name="address" value="{{ old('address', $company?->address) }}"></label>
        <label>Contact Person <input type="text" name="contact_person" value="{{ old('contact_person', $company?->contact_person) }}"></label>
        <label>Contact Email <input type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}"></label>
        <label>Contact Phone <input type="text" name="contact_phone" value="{{ old('contact_phone', $company?->contact_phone) }}"></label>
        <label>OJT Slot Limit <input type="number" name="intern_slot_limit" min="1" value="{{ old('intern_slot_limit', $company?->intern_slot_limit ?? 10) }}" required></label>
        <button type="submit" >{{ $isEditing ? 'Save Changes' : 'Save Organization' }}</button>
    </form>
@unless ($embedded)
</article>
@endunless
