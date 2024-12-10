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
    public function __construct(public $package, public $version)
    {
        
    }  

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Request for Access Has Been Processed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $markdown = 'mails.PDFParserMail.parsing-failed';

        return new Content(
            markdown: $markdown,
            with: [
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
