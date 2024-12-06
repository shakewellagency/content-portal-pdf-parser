<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PackageInitializationJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PageParserJob;

class PDFParse
{
    public static function execute($package, $version)
    {
       
        PackageInitializationJob::withChain([
            new PageParserJob($package, $version),
        ])->dispatch($package, $version);
    }
}
