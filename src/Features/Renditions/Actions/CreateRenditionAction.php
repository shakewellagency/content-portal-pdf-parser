<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Renditions\Actions;


class CreateRenditionAction
{
    public function execute($parameter)
    {
        $renditionModel = config('shakewell-parser.models.rendition_model');

        $rendition = new $renditionModel;

        return (new UpdateRenditionAction)->execute($rendition, $parameter);
    }

}
