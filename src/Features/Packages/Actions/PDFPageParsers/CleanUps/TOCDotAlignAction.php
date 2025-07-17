<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Facades\Log;

class TOCDotAlignAction
{
    public function execute($dom, $renditionPage)
    {
        $hasTOC = false;
        $xpath = new \DOMXPath($dom);
        $pTags = $xpath->query('//p');

        foreach ($pTags as $pTag) {
            $textContent = trim($pTag->textContent);
            $aTags = $pTag->getElementsByTagName('a');

            // Standard TOC format with dots
            if (preg_match('/\.{5,}/', $textContent)) {
                $hasTOC = true;
                $fragment = $this->handleStandardTOC($dom, $pTag, $aTags);
                if ($fragment) {
                    $pTag->parentNode->replaceChild($fragment, $pTag);
                }
            }

            // Alternate TOC format: one <a> tag with embedded label + page (e.g., "TITLE PAGE .......... TP-1")
            elseif ($aTags->length === 1 && preg_match('/\.{5,}/', $aTags->item(0)->textContent)) {
                $hasTOC = true;
                $fragment = $this->handleAlternateTOC($dom, $pTag, $aTags->item(0));
                if ($fragment) {
                    $pTag->parentNode->replaceChild($fragment, $pTag);
                }
            }
        }

        $renditionPage->has_toc = $hasTOC;
        $renditionPage->save();
        $renditionPage->refresh();

        return $dom;
    }

    protected function handleStandardTOC($dom, $pTag, $aTags)
    {
        $class = $pTag->getAttribute('class') ?: 'ft01';
        $style = $pTag->getAttribute('style');
        preg_match('/top:\s*([\d\.]+)px/', $style, $topMatch);
        preg_match('/left:\s*([\d\.]+)px/', $style, $leftMatch);

        $topValue = isset($topMatch[1]) ? (float) $topMatch[1] : 0;
        $leftValue = isset($leftMatch[1]) ? (float) $leftMatch[1] : 0;
        $currentTop = $topValue;

        $fragment = $dom->createDocumentFragment();

        foreach ($aTags as $aTag) {
            $linkText = str_replace("\u{A0}", ' ', $aTag->textContent);
            $href = $aTag->getAttribute('href');
            $items = $this->getLabelAndPageNumber($linkText, $href);

            foreach ($items as $item) {
                $div = $this->createDivBlock($dom, $item, $leftValue, $currentTop, $class);
                $fragment->appendChild($div);
                $currentTop += 36;
            }
        }

        return $fragment;
    }

    protected function handleAlternateTOC($dom, $pTag, $aTag)
    {
        $class = $pTag->getAttribute('class') ?: 'ft01';
        $style = $pTag->getAttribute('style');
        preg_match('/top:\s*([\d\.]+)px/', $style, $topMatch);
        preg_match('/left:\s*([\d\.]+)px/', $style, $leftMatch);

        $topValue = isset($topMatch[1]) ? (float) $topMatch[1] : 0;
        $leftValue = isset($leftMatch[1]) ? (float) $leftMatch[1] : 0;

        $linkText = str_replace("\u{A0}", ' ', $aTag->textContent);
        $href = $aTag->getAttribute('href');
        $items = $this->getLabelAndPageNumber($linkText, $href);

        if (empty($items)) {
            // fallback if no dots/page match — treat as raw line
            $items[] = [
                'label' => trim($linkText),
                'page' => '',
                'href' => $href,
            ];
        }

        $fragment = $dom->createDocumentFragment();
        foreach ($items as $item) {
            $div = $this->createDivBlock($dom, $item, $leftValue, $topValue, $class);
            $fragment->appendChild($div);
            $topValue += 36;
        }

        return $fragment;
    }

    private function getLabelAndPageNumber($textContent, $href)
    {
        $results = [];

        // Accepts TP-1, TOC-1, JC1-1, APPENDIX A-1, etc.
        preg_match_all('/^(.*?)\.{5,}\s*([A-Z0-9\- ]{2,})$/', $textContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $label = trim($match[1]);
            $page = trim($match[2]);

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
        $page = htmlspecialchars($item['page']);
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