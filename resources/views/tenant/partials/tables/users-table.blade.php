@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
@endphp

@unless ($embedded)
<article>
@endunless
    @if ($showHeading)
        <h2>User Management</h2>
    @endif
    <p>Tenant admins manage student, supervisor, and coordinator accounts for this university portal.</p>
    @if ($userDirectory->isEmpty())
        <p>No university portal users yet.</p>
    @else
        <table>
            <thead><tr><th>Name</th><th>Role</th><th>Context</th><th>Verification</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach ($userDirectory as $user)
                    <tr>
                        <td>{{ $user['name'] }}<br><small>{{ $user['email'] }}</small></td>
                        <td><span class="table-badge">{{ strtoupper($user['role'] === 'admin' ? 'internship coordinator' : ($user['role'] === 'supervisor' ? 'company supervisor' : $user['role'])) }}</span></td>
                        <td>{{ $user['context'] }}</td>
                        <td>
                            @if (array_key_exists('email_verified_at', $user))
                                <span class="table-badge">{{ $user['email_verified_at'] ? 'Verified' : 'Pending' }}</span>
                            @else
                                <span class="table-badge">Managed</span>
                            @endif
                        </td>
                        <td><span class="table-badge">{{ ucfirst($user['status']) }}</span></td>
                        <td>
                            <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=users&edit='.$user['key'] }}" title="Edit user" aria-label="Edit user">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span class="sr-only">Edit</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@unless ($embedded)
</article>
@endunless
