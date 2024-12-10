<?php

namespace Shakewellagency\ContentPortalPdfParser\Mails;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParsingFinishedMail extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     */
    public function __construct(public $package, public $version)
    {
        
    }  

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Import has been finished',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $publication = $this->version->publication;
        
        $startedAt = Carbon::parse($this->package->started_at);
        $finishedAt = Carbon::parse($this->package->finished_at);
        $minutes = $startedAt->diffInMinutes($finishedAt);

        $previewLink = url("/publication/{$publication->id}/version/{$this->version->id}/preview/{$this->version->preview_token}");
        $approvedLink = url("/publication/{$publication->id}/version/{$this->version->id}/approved/{$this->version->approved_token}");
        $scheduleLink = url("/nova/resources/versions/{$this->version->id}/edit");
        
        $markdown = 'mails.PDFParserMail.parsing-finished';

        LoggerInfo('Finished Mail has been sent', [
            'package' => $this->package->toArray(),
            'publicationNo' => $publication->publication_no,
            'versionInfo' => json_decode($this->version->version_meta),
            'startedDate' => $this->package->started_at,
            'finishedDate' => $this->package->finished_at,
            'processTime' => round($minutes, 2),
            'previewLink' => $previewLink,
            'approvedLink' => $approvedLink,
            'scheduleLink' => $scheduleLink,
        ]);

        return new Content(
            markdown: $markdown,
            with: [
                'publicationNo' => $publication->publication_no,
                'versionInfo' => json_decode($this->version->version_meta),
                'startedDate' => $this->package->started_at,
                'finishedDate' => $this->package->finished_at,
                'processTime' => round($minutes, 2),
                'previewLink' => $previewLink,
                'approvedLink' => $approvedLink,
                'scheduleLink' => $scheduleLink,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
