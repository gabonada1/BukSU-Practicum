<?php

namespace App\Mail;

use App\Models\TenantPlanApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantPlanApplicationPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantPlanApplication $application,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Stripe payment received - waiting for University Practicum approval | '.$this->application->college_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-plan-application-pending-approval',
            with: [
                'application' => $this->application,
            ],
        );
    }
}
