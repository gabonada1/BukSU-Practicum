@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
    $mode = $mode ?? 'create';
    $isEditing = $mode === 'edit' && filled($editingHour ?? null);
    $hourRecord = $editingHour ?? null;
    $selectedSupervisorName = old('supervisor_name', $hourRecord?->supervisor_name);
    $action = $isEditing
        ? route('tenant.admin.hours.update', ['hour' => $hourRecord])
        : $formActions['hours'];
@endphp

@unless ($embedded)
<article >
@endunless
    @if ($showHeading)
        <h2>{{ $isEditing ? 'Edit Progress / Hour Log' : 'New Progress / Hour Log' }}</h2>
    @endif
    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($isEditing)
            @method('PATCH')
        @endif
        <label>
            Student
            <select name="student_id" required>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected((string) old('student_id', $hourRecord?->student_id) === (string) $student->id)>{{ $student->full_name }}</option>
                @endforeach
            </select>
        </label>
        <label>Log Date <input type="date" name="log_date" value="{{ old('log_date', $hourRecord?->log_date?->toDateString() ?? now()->toDateString()) }}" required></label>
        <label>Hours <input type="number" step="0.5" min="0.5" max="24" name="hours" value="{{ old('hours', $hourRecord?->hours ?? 8) }}" required></label>
        <label>
            Status
            <select name="status" required>
                @foreach ($hourStatuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $hourRecord?->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Company Supervisor Name
            <select name="supervisor_name">
                <option value="">Select supervisor</option>
                @foreach (($supervisorOptions ?? collect()) as $supervisorOption)
                    <option value="{{ $supervisorOption->name }}" @selected($selectedSupervisorName === $supervisorOption->name)>
                        {{ $supervisorOption->name }}@if ($supervisorOption->partnerCompany?->name) - {{ $supervisorOption->partnerCompany->name }}@endif
                    </option>
                @endforeach
                @if (filled($selectedSupervisorName) && ! collect($supervisorOptions ?? [])->contains(fn ($supervisorOption) => $supervisorOption->name === $selectedSupervisorName))
                    <option value="{{ $selectedSupervisorName }}" selected>{{ $selectedSupervisorName }}</option>
                @endif
            </select>
        </label>
        <label>Activity <textarea name="activity" required placeholder="Describe the work completed during this shift">{{ old('activity', $hourRecord?->activity) }}</textarea></label>
        <button type="submit" >{{ $isEditing ? 'Save Changes' : 'Save Progress Log' }}</button>
    </form>
@unless ($embedded)
</article>
@endunless
