<x-email-shell
    eyebrow="College Subscription Updated"
    title="Your college subscription details were updated"
    subtitle="{{ $tenant->name }} has updated plan or renewal details from the University Practicum administration layer."
>
    <p>Hello {{ $recipientName }},</p>

    <p>University Practicum Administration updated your university portal subscription settings.</p>

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
            <td ><strong>Subscription Starts</strong></td>
            <td >{{ $tenant->subscription_starts_at?->format('F d, Y') ?: 'Not set' }}</td>
        </tr>
        <tr>
            <td ><strong>Subscription Expires</strong></td>
            <td >{{ $tenant->subscription_expires_at?->format('F d, Y') ?: 'Open-ended' }}</td>
        </tr>
    </table>

    <p>Review these dates with your practicum team to avoid any interruption to portal access.</p>
</x-email-shell>
