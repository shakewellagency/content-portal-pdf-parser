<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Facades\Storage;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Imagick;

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

        $imagick = new Imagick();
        $imagick->setResolution(300, 300); // Set resolution for high-quality output
        $imagick->readImage($parserFile.'[0]'); // Read the first page of the PDF
        $imagick->setImageFormat('jpg');
        $imagick->writeImage($imagePath);

        $imagick->clear();
        $imagick->destroy();
        $s3FilePath = "{$package->hash}/assets/cover-photo.png";

        Storage::disk('s3temp')->put($s3FilePath, file_get_contents($imagePath));

        $rendition->cover_photo_path = $s3FilePath;
        $rendition->save();
        
        unlink($imagePath);

        return $rendition;
    }
}
