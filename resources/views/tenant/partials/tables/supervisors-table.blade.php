@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
@endphp

@unless ($embedded)
<article>
@endunless
    @if ($showHeading)
        <h2>Company Supervisors</h2>
    @endif
    @if ($supervisors->isEmpty())
        <p>No company supervisors registered yet.</p>
    @else
        <table>
            <thead><tr><th>Name</th><th>Department</th><th>Organization</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach ($supervisors as $supervisor)
                    <tr>
                        <td>{{ $supervisor->name }}</td>
                        <td>{{ $supervisor->department ?: 'No department set' }}</td>
                        <td>{{ $supervisor->partnerCompany?->name ?: 'Unassigned' }}</td>
                        <td>{{ $supervisor->email }}<br><small>{{ $supervisor->email_verified_at ? 'Verified' : 'Pending verification' }}</small></td>
                        <td><span>{{ ucfirst($supervisor->accountStatusLabel()) }}</span></td>
                        <td>
                            <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=supervisors&edit='.$supervisor->id }}" title="Edit supervisor" aria-label="Edit supervisor">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span class="sr-only">Edit</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($supervisors->hasPages())
            <div class="pagination">
                {{ $supervisors->links() }}
            </div>
        @endif
    @endif
@unless ($embedded)
</article>
@endunless
