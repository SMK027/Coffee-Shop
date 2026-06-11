<?php

namespace App\Mail;

use App\Models\LoyaltyCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyPinResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LoyaltyCard $card,
        public readonly string      $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Réinitialisation du code PIN de votre carte de fidélité — Coffee Shop');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.loyalty-pin-reset',
        );
    }
}
