<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions;

use Shakewellagency\ContentPortalPdfParser\Helpers\ConvertHelper;

class UpdatePackageAction
{
    public function execute($package, $parameter)
    {
        $package->file_type = ConvertHelper::safeGet($package, $parameter, 'file_type');
        $package->file_name = ConvertHelper::safeGet($package, $parameter, 'file_name');
        $package->hash =  ConvertHelper::safeGet($package, $parameter, 'hash');
        $package->status = ConvertHelper::safeGet($package, $parameter, 'status');
        $package->location = ConvertHelper::safeGet($package, $parameter, 'location');
        $package->file_path = ConvertHelper::safeGet($package, $parameter, 'file_path');
        $package->request_ip = ConvertHelper::safeGet($package, $parameter, 'request_ip');
        $package->parser_version = ConvertHelper::safeGet($package, $parameter, 'parser_version');
        $package->total_pages = ConvertHelper::safeGet($package, $parameter, 'total_pages');
        $package->started_at = ConvertHelper::safeGet($package, $parameter, 'started_at');
        $package->finished_at = ConvertHelper::safeGet($package, $parameter, 'finished_at');
        $package->failed_exception = ConvertHelper::safeGet($package, $parameter, 'failed_exception');
        $package->save();
        $package->refresh();

        return $package;
    }
}
