<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Str;

class RemoveLastHRTagAction
{
    /**
     * This Action will;
     * 1. remove the last <hr> tag at the end
     */
    public function execute($renditionPage) 
    {
        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);

        $hrs = $dom->getElementsByTagName('hr');

        if ($hrs->length > 0) {
            $lastHr = $hrs->item($hrs->length - 1);

            if ($lastHr && $lastHr->parentNode) {
                $lastHr->parentNode->removeChild($lastHr);
            }
        }

        $modified = $dom->saveHTML();
        $renditionPage->content = json_encode($modified);
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }
}
