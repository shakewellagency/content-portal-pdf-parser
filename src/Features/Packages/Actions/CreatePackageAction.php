<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions;

use Shakewellagency\ContentPortalPdfParser\Models\Package;

class CreatePackageAction
{
    public function execute($parameter)
    {
        $package = new Package;

        return (new UpdatePackageAction)->execute($package, $parameter);
    }

}
