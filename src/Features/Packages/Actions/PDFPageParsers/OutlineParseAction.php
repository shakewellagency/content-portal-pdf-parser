<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers\ContentParserHelper;

class OutlineParseAction
{
    public function execute($renditionPage, $rendition)
    {
        if ($renditionPage->page_no !== 1) {
            return;
        }

        $htmlString = json_decode($renditionPage->content);

        $pattern = '/<h1>Document Outline<\/h1>\s*(<ul>.*<\/ul>)/is';

        if (preg_match($pattern, $htmlString, $matches)) {
            $ulContent = $matches[1];
        } else {
            $ulContent = null; 
        }
        
        $rendition->outline = $this->linkOutlineParser($ulContent, $rendition);
        $rendition->save();
        $rendition->refresh();

        $renditionPage->content = ContentParserHelper::removeOutline($htmlString, true);
        $renditionPage->save();
        return $rendition;
    }


    private function linkOutlineParser($htmlString, $rendition) 
    {
        if(!$htmlString) {
            return null;
        }
        
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Suppress warnings for invalid HTML
        $dom->loadHTML($htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a'); // Get all <a> elements

        foreach ($links as $link) {
            $href = $link->getAttribute('href'); // Get the href attribute
            if (preg_match('/#(\d+)$/', $href, $matches)) { // Extract the number at the end
                $number = $matches[1]; // Get the extracted number
                $newHref = "rendition/{$rendition->id}/rendition-page/{$number}"; // Generate the new href
                $link->setAttribute('href', $newHref); // Set the new href
            }
        }   

        $outline = $dom->saveHTML();

        return json_encode($outline);
    }

}
