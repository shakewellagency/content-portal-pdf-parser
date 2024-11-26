<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Renditions\Actions;

use Shakewellagency\ContentPortalPdfParser\Models\Rendition;

class CreateRenditionAction
{
    public function execute($parameter)
    {
        $rendition = new Rendition;

        return (new UpdateRenditionAction)->execute($rendition, $parameter);
    }

}
