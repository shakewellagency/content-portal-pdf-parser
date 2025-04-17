<?php

namespace Shakewellagency\ContentPortalPdfParser\Mails;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParsingFailedMail extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     */
    public function __construct(
        public $package,
        public $version,
        public $errorMessage
    ) {

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Import has failed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $markdown = 'mails.PDFParserMail.parsing-failed';

        LoggerInfo('Failed Mail has been sent', [
            'package' => $this->package->toArray(),
            'publicationNo' => $this->version->publication->publication_no,
            'versionInfo' => $this->version->version_meta,
            'startedDate' => $this->package->started_at,
            'failedException' => $this->package->failed_exception,
            'errorMessage' => $this->errorMessage,
        ]);
        return new Content(
            markdown: $markdown,
            with: [
                'publicationNo' => $this->version->publication->publication_no,
                'versionInfo' => $this->version->version_meta,
                'startedDate' => $this->package->started_at,
                'failedException' => $this->package->failed_exception,
                'errorMessage' => $this->errorMessage,
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
