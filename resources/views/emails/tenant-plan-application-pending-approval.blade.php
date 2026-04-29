<x-email-shell
    eyebrow="Plan Application Received"
    title="Payment received, waiting for approval"
    subtitle="University Practicum has recorded the Stripe test payment for {{ $application->college_name }}, but the tenant portal is not active yet."
>
    <p>Hello {{ $application->contact_name }},</p>

    <p>Your college plan application has been paid successfully and is now waiting for University Practicum central admin approval.</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" class="email-data-table">
        <tr>
            <td ><strong>College</strong></td>
            <td >{{ $application->college_name }}</td>
        </tr>
        <tr>
            <td ><strong>Selected Plan</strong></td>
            <td >{{ strtoupper($application->selected_plan) }}</td>
        </tr>
        <tr>
            <td ><strong>Payment Status</strong></td>
            <td >{{ strtoupper($application->payment_status) }}</td>
        </tr>
        <tr>
            <td ><strong>Coordinator Email</strong></td>
            <td >{{ $application->admin_email }}</td>
        </tr>
    </table>

    <p class="email-note">
        Important:
        the tenant database, university portal, and coordinator login credentials are <strong>not created yet</strong>.
        They are only created after University Practicum central admin approves your request.
    </p>

    <p>Once approved, the coordinator credentials will be emailed automatically.</p>
</x-email-shell>
