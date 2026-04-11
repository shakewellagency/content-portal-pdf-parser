<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades;

use Shakewellagency\ContentPortalPdfParser\Events\ParsingTriggerEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\LinearizePackagePdfJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PackageInitializationJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PageParserJob;

class PDFParse
{
    public static function execute($package, $version)
    {
        event(new ParsingTriggerEvent($package, $version));
        if (config('shakewell-parser.linearize_on_parse', true)) {
            LinearizePackagePdfJob::dispatch((string) $package->getKey());
        }

        PackageInitializationJob::withChain([
            new PageParserJob($package, $version),
        ])->dispatch($package, $version);
    }
}
