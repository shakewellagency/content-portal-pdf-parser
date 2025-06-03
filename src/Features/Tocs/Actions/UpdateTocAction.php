<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Tocs\Actions;


use Shakewellagency\ContentPortalPdfParser\Helpers\ConvertHelper;

class UpdateTocAction
{
    public function execute($toc, $parameter)
    {
        $toc->name = ConvertHelper::safeGet($toc, $parameter, 'name');
        $toc->page_no = ConvertHelper::safeGet($toc, $parameter, 'page_no');
        $toc->parent_id = ConvertHelper::safeGet($toc, $parameter, 'parent_id');
        $toc->rendition_id = ConvertHelper::safeGet($toc, $parameter, 'rendition_id');
        $toc->order = ConvertHelper::safeGet($toc, $parameter, 'order');
        $toc->save();
        $toc->refresh();

        return $toc;
    }
}
