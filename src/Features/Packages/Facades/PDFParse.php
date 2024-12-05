<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PackageInitializationJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PageParserJob;
use Shakewellagency\ContentPortalPdfParser\Models\Package;
use Shakewellagency\ContentPortalPdfParser\Models\Version;

class PDFParse
{
    public static function execute(Package $package, $version)
    {
        PackageInitializationJob::withChain([
            new PageParserJob($package, $version),
        ])->dispatch($package, $version);
    }
}
