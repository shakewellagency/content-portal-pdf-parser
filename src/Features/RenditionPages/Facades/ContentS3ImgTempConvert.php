<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Facades;

use Shakewellagency\ContentPortalPdfParser\Models\RenditionAsset;
use Illuminate\Support\Facades\Storage;

class ContentS3ImgTempConvert
{
    public static function execute($htmlString)
    {
        // TODO: move inside package
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString); // Suppress warnings for malformed HTML
        
        // Find the <img> tag
        foreach ($dom->getElementsByTagName('img') as $img) {
            $originalSrc = $img->getAttribute('src');
            preg_match('/\/output\/([^?]+)/', $originalSrc, $matches);

            if (isset($matches[1])) {
                $originalSrc = $matches[1];
            }

            $renditionAsset = RenditionAsset::where('file_name', $originalSrc)->first();
            if ($renditionAsset) {
                $img->setAttribute('data-id', $originalSrc);
                $link = $renditionAsset->file_path;
            } else {
                $dataId = $img->getAttribute('data-id');
                $link = RenditionAsset::where('file_name', $dataId)->first() 
                    ? $renditionAsset->file_path : $originalSrc; 

            }
            
            $tempUrl = Storage::disk('s3temp')->temporaryUrl(
                $renditionAsset->file_path,
                now()->addMinutes(10) // set 8 hour
            );

            $img->setAttribute('src', $tempUrl);
        }

        $modified = $dom->saveHTML();

        return $modified;
    }
}
