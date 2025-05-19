<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Str;

class ParseContentValueAction
{
    /**
     * This Action will;
     * 1. remove the last <hr> tag at the end
     */
    public function execute($renditionPage) 
    {
        $renditionPage->refresh();
        $content = json_decode($renditionPage->content);
        $cleanText = strip_tags($content); // Remove HTML tags
        $cleanText = str_replace("\n", ' ', $cleanText); 
        $cleanText = str_replace("\u{A0}", ' ', $cleanText); 
        $cleanedString = preg_replace('/\s+/', ' ', trim($cleanText));
        $cleanedString = preg_replace('/^\/tmp\/[^\s]+/', '', $cleanedString);
    
        $renditionPage->content_value = $cleanedString;
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }
}
