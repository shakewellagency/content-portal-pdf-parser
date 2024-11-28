<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Actions;

use Shakewellagency\ContentPortalPdfParser\Models\RenditionPage;

class CreateRenditionPageAction
{
    public function execute($parameter)
    {
        $renditionPage = new RenditionPage;

        return (new UpdateRenditionPageAction)->execute($renditionPage, $parameter);
    }

}
