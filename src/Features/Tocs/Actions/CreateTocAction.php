<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Tocs\Actions;


class CreateTocAction
{
    public function execute($parameter)
    {
        $tocModel = config('shakewell-parser.models.toc_model');

        $toc = new $tocModel;

        return (new UpdateTocAction)->execute($toc, $parameter);
    }

}
