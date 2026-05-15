<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $restaurantName,
        public readonly string $roleLabel,
        public readonly string $loginEmail,
        public readonly ?string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been added to {$this->restaurantName} on BisLami",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.staff-welcome',
        );
    }
}
