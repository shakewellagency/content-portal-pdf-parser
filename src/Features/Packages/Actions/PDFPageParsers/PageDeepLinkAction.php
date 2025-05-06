<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;


class PageDeepLinkAction
{
    public function execute($renditionPage) 
    {
        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);
    
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
    
            if (preg_match('/parser_[^"]+\.html#(\d+)/', $href, $matches)) {
                $idNumber = $matches[1];
                $anchor->setAttribute('href', 'page' . $idNumber . '-div');
            }
        }
    
        $modified = $dom->saveHTML();
        $renditionPage->content = json_encode($modified);
        $renditionPage->save();
        $renditionPage->refresh();
    
        return $renditionPage;
    }
}
