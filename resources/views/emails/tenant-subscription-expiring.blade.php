<x-email-shell
    eyebrow="College License Reminder"
    title="Your university portal access is nearing expiry"
    subtitle="{{ $tenant->name }} is approaching its license deadline. Please review the remaining time and coordinate renewal."
>
    <p>Hello {{ $recipientName }},</p>

    <p>This is a reminder that your university portal access is nearing its renewal deadline.</p>

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
            <td ><strong>Days Remaining</strong></td>
            <td >{{ $daysRemaining }}</td>
        </tr>
        <tr>
            <td ><strong>License Expiry</strong></td>
            <td >{{ $tenant->subscription_expires_at?->format('F d, Y') ?: 'Open-ended' }}</td>
        </tr>
    </table>

    <p>Please coordinate with University Practicum Administration before the deadline to keep the university portal accessible.</p>
</x-email-shell>
