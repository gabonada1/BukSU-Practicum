<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantPasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $name,
        public string $code,
        protected TenantUrlGenerator $urlGenerator,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your password reset code | '.$this->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-password-reset-code',
            with: [
                'tenant' => $this->tenant,
                'name' => $this->name,
                'code' => $this->code,
                'resetUrl' => rtrim($this->urlGenerator->tenantBaseUrl($this->tenant), '/').'/reset-password',
            ],
        );
    }
}
