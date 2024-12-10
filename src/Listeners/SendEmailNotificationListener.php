<?php

namespace Shakewellagency\ContentPortalPdfParser\Listeners;

use Illuminate\Support\Facades\Mail;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingFailedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingFinishedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingStartedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingTriggerEvent;
use Shakewellagency\ContentPortalPdfParser\Mails\ParsingFailedMail;
use Shakewellagency\ContentPortalPdfParser\Mails\ParsingFinishedMail;
use Shakewellagency\ContentPortalPdfParser\Mails\ParsingStartedMail;
use Shakewellagency\ContentPortalPdfParser\Mails\ParsingTriggerMail;

class SendEmailNotificationListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(object $event)
    {
        if (! empty($event->package)) {
            $event->package->refresh();
        }
        $eventClass = get_class($event);

        $mail = match ($eventClass) {
            ParsingTriggerEvent::class => new ParsingTriggerMail($event->package, $event->version),
            ParsingStartedEvent::class => new ParsingStartedMail($event->package, $event->version), 
            ParsingFinishedEvent::class => new ParsingFinishedMail($event->package, $event->version), 
            ParsingFailedEvent::class => new ParsingFailedMail($event->package, $event->version, $event->errorMessage),
            default => throw new \Exception('Unexpected match value'),
        };

        $destinations[] = [
            'email' => 'kylyn.l@shakewell.agency',
            'to' => 'Kylyn L',
        ];

        Mail::bcc($destinations)->send($mail);
    }
}
