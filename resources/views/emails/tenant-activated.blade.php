<x-email-shell
    eyebrow="University Portal Activated"
    title="College portal access has been restored"
    subtitle="{{ $tenant->name }} is active again and its coordinator accounts can sign in to the portal."
>
    <p>Hello {{ $recipientName }},</p>

    <p>University Practicum Administration has reactivated your university portal.</p>

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
            <td >Active</td>
        </tr>
        <tr>
            <td ><strong>Subscription Expires</strong></td>
            <td >{{ $tenant->subscription_expires_at?->format('F d, Y') ?: 'Open-ended' }}</td>
        </tr>
    </table>

    <p>You may sign in again and continue managing your practicum portal.</p>
</x-email-shell>
