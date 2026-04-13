@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
    $userRecord = $editingUser ?? null;
    $model = $userRecord['model'] ?? null;
    $canAssignRole = (bool) ($tenantPermissions['user.role.assign'] ?? false);
    $canSuspendUser = (bool) ($tenantPermissions['user.suspend'] ?? false);
    $action = filled($userRecord)
        ? route('tenant.admin.users.update', ['type' => $userRecord['type'], 'id' => $userRecord['id']])
        : '#';
@endphp

@unless ($embedded)
<article >
@endunless
    @if ($showHeading)
        <h2>Edit User</h2>
    @endif

    @if ($userRecord)
        <form method="POST" action="{{ $action }}">
            @csrf
            @method('PATCH')
            <label>Name <input type="text" value="{{ $userRecord['name'] }}" readonly></label>
            <label>Email <input type="text" value="{{ $userRecord['email'] }}" readonly></label>
            @if ($canAssignRole)
                <label>
                    Role
                    <select name="role" required>
                        @foreach ($userRoleOptions as $roleOption)
                            <option value="{{ $roleOption }}" @selected(old('role', $userRecord['role']) === $roleOption)>{{ $roleOption === 'admin' ? 'Internship Coordinator' : ($roleOption === 'supervisor' ? 'Company Supervisor' : ucfirst($roleOption)) }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <input type="hidden" name="role" value="{{ old('role', $userRecord['role']) }}">
                <label>
                    Role
                    <input type="text" value="{{ $userRecord['role'] === 'admin' ? 'Internship Coordinator' : ($userRecord['role'] === 'supervisor' ? 'Company Supervisor' : ucfirst($userRecord['role'])) }}" readonly>
                </label>
            @endif
            @if ($canSuspendUser)
                <label>
                    Access
                    <select name="is_active" required>
                        <option value="1" @selected((string) old('is_active', (int) $userRecord['is_active']) === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', (int) $userRecord['is_active']) === '0')>Suspended</option>
                    </select>
                </label>
            @else
                <input type="hidden" name="is_active" value="{{ old('is_active', (int) $userRecord['is_active']) }}">
                <label>
                    Access
                    <input type="text" value="{{ (int) $userRecord['is_active'] === 1 ? 'Active' : 'Suspended' }}" readonly>
                </label>
            @endif
            <p >
                Update the selected account's role and access state while keeping the existing email and password intact.
            </p>
            @if ($canAssignRole || $canSuspendUser)
                <button type="submit" >Save User</button>
            @endif
        </form>
    @endif
@unless ($embedded)
</article>
@endunless
