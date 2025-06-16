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

class ParsingTriggerMail extends Mailable
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
            subject: 'An import process is about to start',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $markdown = 'mails.PDFParserMail.parsing-triggered';

        LoggerInfo("package:{$this->package->id} - Trigger Mail has been sent", [
            'triggerDate' => Carbon::now()->timezone(config('shakewell-parser.timezone')),
            'publicationNo' => $this->version->publication->publication_no,
            'versionInfo' => $this->version->version_meta,
        ]);

        return new Content(
            markdown: $markdown,
            with: [
                'triggerDate' => Carbon::now()->timezone(config('shakewell-parser.timezone')),
                'publicationNo' => $this->version->publication->publication_no,
                'versionInfo' => $this->version->version_meta,
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
