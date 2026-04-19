@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
@endphp

@unless ($embedded)
<article>
@endunless
    @if ($showHeading)
        <h2>Students</h2>
    @endif
    @if ($students->isEmpty())
        <p>No students enrolled yet.</p>
    @else
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Verification</th><th>Status</th><th>Hours</th><th>Action</th></tr></thead>
            <tbody>
                @foreach ($students as $student)
                    <tr>
                        <td>{{ $student->full_name }}<br><small>{{ $student->partnerCompany?->name ?: 'Unassigned organization' }}</small></td>
                        <td>{{ $student->email }}</td>
                        <td><span>{{ $student->email_verified_at ? 'Verified' : 'Pending' }}</span></td>
                        <td><span>{{ $student->status }}</span></td>
                        <td>{{ $student->completed_hours }} / {{ $student->required_hours }}</td>
                        <td>
                            <div class="link-row">
                                <a class="action-icon-button" href="{{ $dashboardBaseUrl.'?section=students&student_applications='.$student->id }}" title="View student applications" aria-label="View student applications">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <span class="sr-only">Applications</span>
                                </a>
                                <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=students&edit='.$student->id }}" title="Edit student" aria-label="Edit student">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span class="sr-only">Edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($students->hasPages())
            <div class="pagination">
                {{ $students->links() }}
            </div>
        @endif
    @endif
@unless ($embedded)
</article>
@endunless
