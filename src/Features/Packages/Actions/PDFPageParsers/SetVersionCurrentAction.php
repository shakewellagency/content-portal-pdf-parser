<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

class SetVersionCurrentAction
{
    public function execute($version, $rendition)
    {
        $version->renditions()->update(['is_current' => false]);

        $rendition->is_current = true;
        $rendition->save();
        $rendition->refresh();
        return $rendition;
    }

}
