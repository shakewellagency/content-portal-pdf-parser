<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;

use Illuminate\Support\Facades\Storage;

class GetS3ParserFileTempAction
{
    public function execute($package)
    {

        $fileContent = Storage::disk(config('shakewell-parser.s3'))->get($package->file_path);
        $currentTimestamp = now()->timestamp; 
        $tempHtmlPath = tempnam(sys_get_temp_dir(), "parser_file_{$package->hash}_{$currentTimestamp}_");
        $parserFile = $tempHtmlPath . '.'.$package->file_type;
        rename($tempHtmlPath, $parserFile);

        file_put_contents($parserFile, $fileContent);

        return $parserFile;
    }
}
