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

class ParsingStartedMail extends Mailable
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
            subject: 'An import has been started processing',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $markdown = 'mails.PDFParserMail.parsing-started';

        LoggerInfo('Started Mail has been sent', [
            'package' => $this->package->toArray(),
            'publicationNo' => $this->version->publication->publication_no,
            'versionInfo' => json_decode($this->version->version_meta),
            'startedDate' => $this->package->started_at,
        ]);

        return new Content(
            markdown: $markdown,
            with: [
                'publicationNo' => $this->version->publication->publication_no,
                'versionInfo' => json_decode($this->version->version_meta),
                'startedDate' => $this->package->started_at,
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
