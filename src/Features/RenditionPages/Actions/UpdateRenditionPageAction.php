<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Actions;


use Shakewellagency\ContentPortalPdfParser\Helpers\ConvertHelper;

class UpdateRenditionPageAction
{
    public function execute($renditionPage, $parameter)
    {
        $renditionPage->rendition_id = ConvertHelper::safeGet($renditionPage, $parameter, 'rendition_id');
        $renditionPage->slug = ConvertHelper::safeGet($renditionPage, $parameter, 'slug');
        $renditionPage->page_no =  ConvertHelper::safeGet($renditionPage, $parameter, 'page_no');
        $renditionPage->content = ConvertHelper::safeGet($renditionPage, $parameter, field: 'content');
        $renditionPage->is_parsed = ConvertHelper::safeGet($renditionPage, $parameter, 'is_parsed', true);
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }
}
