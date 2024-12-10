<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;

use Illuminate\Support\Facades\Storage;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions\FilePathNotFoundException;

class GenerateHashAction
{

    public function validate($version, $filePath)
    {
        
        $fileContent = Storage::disk(config('shakewell-parser.s3'))->get($filePath);

        $hash = hash('sha1', $fileContent);

        $hasHash = $version->whereHas('renditions.package', function ($query) use ($hash) {
            $query->where('hash', $hash);
        })->exists();

        return $hasHash;
    }

    public function execute($package)
    {

        $fileContent = Storage::disk(config('shakewell-parser.s3'))->get($package->file_path);

        if (!$fileContent) {
            throw new FilePathNotFoundException('PDF does not exist on S3');
        }

        $hash = hash('sha1', $fileContent);

        $package->hash = $hash;
        $package->save();
        $package->refresh();
    
       
        return $package;
    }
}
