<?php

namespace Shakewellagency\ContentPortalPdfParser\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingFailedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingFinishedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingStartedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingTriggerEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers\ModifyPublishedNamespaceHelper;
use Shakewellagency\ContentPortalPdfParser\Listeners\SendEmailNotificationListener;

class ParserServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->listener();

        $this->publishes([
            __DIR__.'/../databases/migrations' => database_path('migrations'),
            __DIR__.'/../Enums' => app_path('Enums'),
            __DIR__ . '/../config/shakewell-parser.php' => config_path('shakewell-parser.php'),
            __DIR__ . '/../Views/mail' => resource_path('views/mails/PDFParserMail'),
        ], 'parser-assets');

        ModifyPublishedNamespaceHelper::modifyPublishedNamespace(
            app_path('Enums'), 
            'Shakewellagency\\ContentPortalPdfParser\\Enums', 
            'App\\Enums'
        );
    }

    protected function listener()
    {
        Event::listen(
            ParsingTriggerEvent::class,
            SendEmailNotificationListener::class
        );

        Event::listen(
            ParsingStartedEvent::class,
            SendEmailNotificationListener::class
        );

        Event::listen(
            ParsingFinishedEvent::class,
            SendEmailNotificationListener::class
        );

        Event::listen(
            ParsingFailedEvent::class,
            SendEmailNotificationListener::class
        );
    }
}
