<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutomatedKpiReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $reportData
     * @param array<int, array{data: string, name: string, mime: string}> $attachmentsList
     */
    public function __construct(
        public array $reportData,
        public array $attachmentsList = []
    ) {}

    public function envelope(): Envelope
    {
        $reportTitle = $this->reportData['reportTitle'] ?? 'Rapport de Performance';
        $periodLabel = $this->reportData['periodLabel'] ?? '';
        $subject = "📊 {$reportTitle}" . ($periodLabel ? " ({$periodLabel})" : '') . " — MetricsDash";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.kpi-summary-report',
            text: 'emails.kpi-summary-report-text',
            with: $this->reportData,
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $mailAttachments = [];

        foreach ($this->attachmentsList as $att) {
            if (! empty($att['data'])) {
                $mailAttachments[] = Attachment::fromData(
                    fn () => $att['data'],
                    $att['name'] ?? 'rapport.pdf'
                )->withMime($att['mime'] ?? 'application/pdf');
            } elseif (! empty($att['path']) && file_exists($att['path'])) {
                $mailAttachments[] = Attachment::fromPath($att['path'])
                    ->as($att['name'] ?? basename($att['path']))
                    ->withMime($att['mime'] ?? 'application/pdf');
            }
        }

        return $mailAttachments;
    }
}
