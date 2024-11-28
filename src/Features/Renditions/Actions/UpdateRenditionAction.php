<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Renditions\Actions;


use Shakewellagency\ContentPortalPdfParser\Helpers\ConvertHelper;

class UpdateRenditionAction
{
    public function execute($rendition, $parameter)
    {
        $rendition->version_id = ConvertHelper::safeGet($rendition, $parameter, 'version_id');
        $rendition->package_id =  ConvertHelper::safeGet($rendition, $parameter, 'package_id');
        $rendition->type = ConvertHelper::safeGet($rendition, $parameter, field: 'type');
        $rendition->summary = ConvertHelper::safeGet($rendition, $parameter, 'summary');
        $rendition->outline = ConvertHelper::safeGet($rendition, $parameter, 'outline');
        $rendition->is_parsed = ConvertHelper::safeGet($rendition, $parameter, 'is_parsed', true);
        $rendition->save();
        $rendition->refresh();

        return $rendition;
    }
}
