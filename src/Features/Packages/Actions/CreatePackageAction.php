<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions;

class CreatePackageAction
{
    public function execute($parameter)
    {
        $packageModel = config('shakewell-parser.package_model');

        $package = new $packageModel;

        return (new UpdatePackageAction)->execute($package, $parameter);
    }

}
