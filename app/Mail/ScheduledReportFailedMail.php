<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alerte envoyée aux administrateurs quand un rapport planifié échoue (PU-43). */
class ScheduledReportFailedMail extends Mailable
{
    public function __construct(
        public readonly string $reportName,
        public readonly string $errorMessage,
        public readonly string $nextAttempt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[MetricsDash] Échec du rapport planifié « {$this->reportName} »");
    }

    public function content(): Content
    {
        $html = '<p>L\'envoi du rapport planifié <strong>'.e($this->reportName).'</strong> a échoué.</p>'
            .'<p>Erreur : '.e($this->errorMessage).'</p>'
            .'<p>Prochaine tentative : '.e($this->nextAttempt).'</p>'
            .'<p>Consultez l\'historique des envois dans Rapports &amp; Exports &gt; Planification.</p>';

        return new Content(htmlString: $html);
    }
}
