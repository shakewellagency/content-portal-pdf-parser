<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;


class UnlinkTempFileAction
{
    public function execute()
    {
        $tempHtml = tempnam(sys_get_temp_dir(), "parser_");
        $tempDirectory = dirname($tempHtml);
        $filesWithExtension = glob($tempDirectory . "/parser_*.*");
        $filesWithoutExtension = glob($tempDirectory . "/parser_*");
        $allFiles = array_merge($filesWithExtension, $filesWithoutExtension);
        $allFiles = array_unique($allFiles);

        foreach ($allFiles as $file) {
            unlink($file);
        }
    }

}
