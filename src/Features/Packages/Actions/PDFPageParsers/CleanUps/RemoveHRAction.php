<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Str;

class RemoveHRAction
{

    public function execute($dom) 
    {
        $hrs = $dom->getElementsByTagName('hr');

        if ($hrs->length > 0) {
            $lastHr = $hrs->item($hrs->length - 1);

            if ($lastHr && $lastHr->parentNode) {
                $lastHr->parentNode->removeChild($lastHr);
            }
        }
        return $dom;
    }
}
