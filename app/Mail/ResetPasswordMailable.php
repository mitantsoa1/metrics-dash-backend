<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $userName;

    public function __construct(string $resetUrl, string $userName = 'Utilisateur')
    {
        $this->resetUrl = $resetUrl;
        $this->userName = $userName;
    }

    public function build(): self
    {
        return $this->subject('Réinitialisation de votre mot de passe - MetricsDash')
                    ->view('emails.reset-password')
                    ->with([
                        'resetUrl' => $this->resetUrl,
                        'userName' => $this->userName,
                    ]);
    }
}
