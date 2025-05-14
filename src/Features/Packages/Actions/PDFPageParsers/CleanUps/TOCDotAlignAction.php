<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Str;

class TOCDotAlignAction
{

    public function execute($dom) 
    {
       $xpath = new \DOMXPath($dom);

        // Query all <p> tags
        $pTags = $xpath->query('//p');

        foreach ($pTags as $pTag) {
            $textContent = $pTag->textContent;

            if (preg_match('/\.{5,}/', $textContent)) {

                $style = $pTag->getAttribute('style');

                preg_match('/left:(\d+)px/', $style, $matches);
                $left = isset($matches[1]) ? $matches[1] : 0;

                $top = $this->extractTop($style);

                preg_match('/^(.*?)\s*\.*\s*(\d+)$/', $textContent, $textMatches);
                $label = isset($textMatches[1]) ? trim($textMatches[1]) : '';
                $pageNumber = isset($textMatches[2]) ? $textMatches[2] : '';

                $div = $dom->createElement('div');
                $divStyle = "position:absolute;top:{$top}px;left:{$left}px;display:flex;justify-content:space-between;width:calc((100% - {$left}px) * 0.931);";
                $div->setAttribute('style', $divStyle);

                if ($pTag->hasAttribute('class')) {
                    $div->setAttribute('class', $pTag->getAttribute('class'));
                }

                $aHref = '#';
                if ($pTag->getElementsByTagName('a')->length > 0) {
                    $aHref = $pTag->getElementsByTagName('a')->item(0)->getAttribute('href');
                }

                $aTag = $dom->createElement('a');
                $aTag->setAttribute('href', $aHref);
                $labelP = $dom->createElement('p', htmlspecialchars($label));
                $aTag->appendChild($labelP);
                $div->appendChild($aTag);

                $separatorP = $dom->createElement('p');
                $separatorP->setAttribute('style', 'flex:1; border-bottom:2px dotted #000; margin:0 2px;');
                $div->appendChild($separatorP);

                $pageP = $dom->createElement('p', htmlspecialchars($pageNumber));
                $div->appendChild($pageP);

                $pTag->parentNode->replaceChild($div, $pTag);
            }
        }

        return $dom;
    }

    // Helper method to extract top value from style
    private function extractTop($style)
    {
        preg_match('/top:(\d+)px/', $style, $matches);
        return isset($matches[1]) ? $matches[1] : 0;
    }
}
