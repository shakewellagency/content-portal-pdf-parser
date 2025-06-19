<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Str;

class TOCDotAlignAction
{

    public function execute($dom, $renditionPage)
    {
        $hasTOC = false;
        $xpath = new \DOMXPath($dom);
        $pTags = $xpath->query('//p');

        foreach ($pTags as $pTag) {
            $textContent = $pTag->textContent;

            if (preg_match('/\.{5,}/', $textContent)) {
                $hasTOC = true;
                $aTags = $pTag->getElementsByTagName('a');

                if ($aTags->length > 0) {
                    $class = $pTag->getAttribute('class') ?: 'ft01';
                    $style = $pTag->getAttribute('style');
                    preg_match('/top:\s*([\d\.]+)px/', $style, $topMatch);
                    preg_match('/left:\s*([\d\.]+)px/', $style, $leftMatch);

                    $topValue = isset($topMatch[1]) ? (float) $topMatch[1] : 0;
                    $leftValue = isset($leftMatch[1]) ? (float) $leftMatch[1] : 0;
                    $currentTop = $topValue;

                    $fragment = $dom->createDocumentFragment();

                    foreach ($aTags as $aTag) {
                        $linkText = $aTag->textContent;
                        $href = $aTag->getAttribute('href');
                        $linkText = str_replace("\u{A0}", ' ', $linkText);
                        $items = $this->getLabelAndPageNumber($linkText, $href);

                        foreach ($items as $item) {
                            $div = $this->createDivBlock($dom, $item, $leftValue, $currentTop, $class);
                            $fragment->appendChild($div);
                            $currentTop += 32;
                        }
                    }

                    $pTag->parentNode->replaceChild($fragment, $pTag);
                }
            }
        }

        $renditionPage->has_toc = $hasTOC;
        $renditionPage->save();
        $renditionPage->refresh();

        return $dom;
    }

    private function getLabelAndPageNumber($textContent, $href)
    {
        $results = [];

        // Match: label text, followed by dots, then page number
        preg_match_all('/(.*?)\.{5,}\s*(\d+)/', $textContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $label = trim($match[1]);
            $page = (int) $match[2];

            $results[] = [
                'label' => $label,
                'page'  => $page,
                'href'  => $href,
            ];
        }

        return $results;
    }

    private function createDivBlock($dom, $item, $leftValue, $topValue, $class)
    {
        $label = htmlspecialchars($item['label']);
        $page = $item['page'];
        $href = htmlspecialchars($item['href']);

        $div = $dom->createElement('div');
        $div->setAttribute('style', "position:absolute; top:{$topValue}px; left:{$leftValue}px; display:flex; justify-content:space-between; width: calc((100% - {$leftValue}px) * 0.931);");
        $div->setAttribute('class', $class);

        $a = $dom->createElement('a');
        $a->setAttribute('href', $href);

        $pLabel = $dom->createElement('p', $label);
        $a->appendChild($pLabel);

        $pDotted = $dom->createElement('p');
        $pDotted->setAttribute('style', 'flex:1; border-bottom:3.5px dotted #000; margin:4px 2px;');

        $pPage = $dom->createElement('p', $page);

        $div->appendChild($a);
        $div->appendChild($pDotted);
        $div->appendChild($pPage);

        return $div;
    }
}
