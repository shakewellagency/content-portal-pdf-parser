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

        $chain = [new PageParserJob($package, $version)];
        if (config('shakewell-parser.linearize_on_parse', true)) {
            $chain[] = new LinearizePackagePdfJob((string) $package->getKey());
        }

        PackageInitializationJob::withChain($chain)->dispatch($package, $version);
    }
}
