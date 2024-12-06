<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Actions;


class CreateRenditionPageAction
{
    public function execute($parameter)
    {
        $renditionPageModel = config('shakewell-parser.models.rendition_page_model');
        
        $renditionPage = new $renditionPageModel;

        return (new UpdateRenditionPageAction)->execute($renditionPage, $parameter);
    }

}
