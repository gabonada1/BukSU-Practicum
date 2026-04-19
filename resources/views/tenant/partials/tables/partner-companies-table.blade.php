@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
@endphp

@unless ($embedded)
<article>
@endunless
    @if ($showHeading)
        <h2>Partner Organizations</h2>
    @endif
    @if ($companies->isEmpty())
        <p>No partner organizations on file yet.</p>
    @else
        <table>
            <thead><tr><th>Organization</th><th>Positions</th><th>Required Documents</th><th>Company Supervisor Details</th><th>Slots</th><th>Action</th></tr></thead>
            <tbody>
                @foreach ($companies as $company)
                    <tr>
                        <td>
                            <strong>{{ $company->name }}</strong><br>
                            <small>{{ $company->industry ?: 'No industry type set' }}</small><br>
                            <small>{{ $company->address ?: 'No address set' }}</small>
                        </td>
                        <td>{{ implode(', ', $company->availablePositionsList()) ?: 'No positions listed' }}</td>
                        <td>{{ implode(', ', $company->requiredDocumentsList()) ?: 'No required documents listed' }}</td>
                        <td>
                            @if ($company->supervisors->isNotEmpty())
                                {{ $company->supervisors->pluck('name')->implode(', ') }}
                            @else
                                {{ $company->contact_person ?: 'No company supervisor assigned yet' }}
                            @endif
                        </td>
                        <td>{{ $company->intern_slot_limit }}</td>
                        <td>
                            <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=companies&edit='.$company->id }}" title="Edit company" aria-label="Edit company">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span class="sr-only">Edit</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($companies->hasPages())
            <div class="pagination">
                {{ $companies->links() }}
            </div>
        @endif
    @endif
@unless ($embedded)
</article>
@endunless
