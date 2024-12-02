<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Shakewellagency\ContentPortalPdfParser\Models\RenditionAsset;

class PageAssetDataIDAction
{
    public function execute($renditionPage) {

        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);

        foreach ($dom->getElementsByTagName('img') as $img) {
            $originalSrc = $img->getAttribute('src');
            preg_match('/\/output\/([^?]+)/', $originalSrc, $matches);

            if (isset($matches[1])) {
                $originalSrc = $matches[1];
            }

            $renditionAsset = RenditionAsset::where('file_name', $originalSrc)->first();
            if ($renditionAsset) {
                $img->setAttribute('asset-path', $renditionAsset->file_path);
            }
        }

        $modified = $dom->saveHTML();
        $renditionPage->content = json_encode($modified);
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }   
}
