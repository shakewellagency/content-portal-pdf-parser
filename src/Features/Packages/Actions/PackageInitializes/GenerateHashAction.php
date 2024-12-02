<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;

use Illuminate\Support\Facades\Storage;

class GenerateHashAction
{
    public function execute($package)
    {
        $fileContent = Storage::disk(config('shakewell-parser.s3'))->get($package->file_path);
        $hash = hash('sha1', $fileContent);

        $package->hash = $hash;
        $package->save();
        $package->refresh();

        return $package;
    }
}
