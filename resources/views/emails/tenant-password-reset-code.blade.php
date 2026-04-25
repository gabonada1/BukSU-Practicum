<x-email-shell
    eyebrow="Password Reset"
    title="Your reset code"
    subtitle="Use this code to create a new password for your {{ $tenant->name }} portal account."
>
    <p>Hello {{ $name }},</p>

    <p>We received a request to reset your University Practicum portal password.</p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" class="email-data-table">
        <tr>
            <td><strong>College</strong></td>
            <td>{{ $tenant->name }}</td>
        </tr>
        <tr>
            <td><strong>Reset Code</strong></td>
            <td><strong>{{ $code }}</strong></td>
        </tr>
    </table>

    <p class="email-button-wrap">
        <a href="{{ $resetUrl }}" class="email-button">Reset Password</a>
    </p>

    <p class="email-note">This code expires in 15 minutes. If you did not request a password reset, you can ignore this email.</p>
</x-email-shell>
