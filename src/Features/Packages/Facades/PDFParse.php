<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades;

use Carbon\Carbon;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingStartedEvent;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingTriggerEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\UnlinkTempFileAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PackageInitializationJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PageParserJob;

class PDFParse
{
    public static function execute($package, $version)
    {
        //TODO: remove this
        (new UnlinkTempFileAction)->execute();
        //---- remove this

        event(new ParsingTriggerEvent($package, $version));

        $parserFile = (new GetS3ParserFileTempAction)->execute($package);
        
        PackageInitializationJob::withChain([
            new PageParserJob($package, $version, $parserFile),
        ])->dispatch($package, $version, $parserFile);
    }
}
