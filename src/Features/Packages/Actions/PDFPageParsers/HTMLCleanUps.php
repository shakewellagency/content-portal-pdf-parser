<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps\AdjustPaddingTopAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps\RemoveHRAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps\TOCDotAlignAction;

class HTMLCleanUps
{
    /**
     * This action will:
     * 1. Remove the last <hr> tag at the end.
     * 2. Fix the TOC alignment.
     * 3. Add 2px padding to the <p> tag.
     * 4. Change top: 13px to top: 10px so it won’t overlap the logo.
     */
    public function execute($renditionPage)
    {
        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);

        
        $dom = (new TOCDotAlignAction)->execute($dom);
        $dom = (new RemoveHRAction)->execute($dom);
        $dom = (new AdjustPaddingTopAction)->execute($dom);

        $modified = $dom->saveHTML();

        $renditionPage->content = json_encode($modified);
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }
}
