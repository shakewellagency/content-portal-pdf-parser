<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;

class CoverPhotoAction
{
    public function execute(
        $renditionPage, 
        $rendition, 
        $package
    ) {

        if ($renditionPage->page_no !== 1) {
            return;
        }

        $parserFile = (new GetS3ParserFileTempAction)->execute($package);

        $tempHtmlPath = tempnam(sys_get_temp_dir(), "image_");
        $imagePath = $tempHtmlPath . '.jpg';
        rename($tempHtmlPath, $imagePath);

        $command = sprintf(
            'magick -density 300 %s[0] -resize 100%% %s', 
            escapeshellarg($parserFile),
            escapeshellarg($imagePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            // Log the error for debugging
            Log::error('magick command failed', [
                'command' => $command,
                'output' => $output,
                'returnVar' => $returnVar,
            ]);
        }

        $s3FilePath = "{$package->hash}/assets/cover-photo.jpg";

        Storage::disk('s3temp')->put($s3FilePath, file_get_contents($imagePath));
  
        $rendition->cover_photo_path = $s3FilePath;
        $rendition->save();

        unlink($imagePath);

        return $rendition;
    }
}
