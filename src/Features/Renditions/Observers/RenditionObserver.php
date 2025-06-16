<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Renditions\Observers;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\ParseTOCAction;

class RenditionObserver
{
    public function updated($rendition): void
    {
        if ($rendition->outline && $rendition->isDirty('outline')) {
            (new ParseTOCAction)->execute($rendition);
        }
    }

    public function saved($rendition): void
    {
        if ($rendition->outline) {
            (new ParseTOCAction)->execute($rendition);
        }
    }
}
