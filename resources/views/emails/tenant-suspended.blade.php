<x-email-shell
    eyebrow="University Portal Suspended"
    title="College portal access has been suspended"
    subtitle="{{ $tenant->name }} can no longer access the university portal until the license is restored."
>
    <p>Hello {{ $recipientName }},</p>

    <p>Your university portal access is currently suspended.</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" class="email-data-table">
        <tr>
            <td ><strong>College</strong></td>
            <td >{{ $tenant->name }}</td>
        </tr>
        <tr>
            <td ><strong>License Tier</strong></td>
            <td >{{ strtoupper($tenant->plan) }}</td>
        </tr>
        <tr>
            <td ><strong>Status</strong></td>
            <td >Suspended</td>
        </tr>
        <tr>
            <td ><strong>License Expiry</strong></td>
            <td >{{ $tenant->subscription_expires_at?->format('F d, Y') ?: 'Open-ended' }}</td>
        </tr>
    </table>

    <p>Please contact University Practicum Administration to reactivate the university portal.</p>
</x-email-shell>
