<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeePasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $employee,
        public readonly string $resetUrl,
        public readonly bool   $isNewAccount = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isNewAccount
            ? 'Bienvenue — Définissez votre mot de passe Coffee Shop'
            : 'Réinitialisation de votre mot de passe — Coffee Shop';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.employee-password-reset',
        );
    }
}
