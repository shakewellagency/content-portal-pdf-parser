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

            // Format 1: Standard TOC format (numbers like "1. Introduction ........ 3")
            if ($this->matchesFormatOne($aTags)) {
                $hasTOC = true;
                $this->handleFormatOne($dom, $pTag, $aTags);
            }

            // Format 2: Alternate label-page (e.g., "TITLE PAGE .......... TP-1")
            elseif ($this->matchesFormatTwo($aTags)) {
                $hasTOC = true;
                $fragment = $this->handleFormatTwo($dom, $pTag, $aTags->item(0));
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

    protected function matchesFormatOne($aTags)
    {
        if ($aTags->length === 0) return false;

        foreach ($aTags as $aTag) {
            if (preg_match('/(.*?)\.{5,}\s*\d+/', $aTag->textContent)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesFormatTwo($aTags)
    {
        return $aTags->length === 1 && preg_match('/\.{5,}/', $aTags->item(0)->textContent);
    }

    protected function handleFormatOne($dom, $pTag, $aTags)
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
            $items = $this->getFormatOneItems($linkText, $href);

            foreach ($items as $item) {
                $div = $this->createDivBlock($dom, $item, $leftValue, $currentTop, $class);
                $fragment->appendChild($div);
                $currentTop += 36;
            }
        }

        $pTag->parentNode->replaceChild($fragment, $pTag);
    }

    protected function handleFormatTwo($dom, $pTag, $aTag)
    {
        $class = $pTag->getAttribute('class') ?: 'ft01';
        $style = $pTag->getAttribute('style');
        preg_match('/top:\s*([\d\.]+)px/', $style, $topMatch);
        preg_match('/left:\s*([\d\.]+)px/', $style, $leftMatch);

        $topValue = isset($topMatch[1]) ? (float) $topMatch[1] : 0;
        $leftValue = isset($leftMatch[1]) ? (float) $leftMatch[1] : 0;

        $linkText = str_replace("\u{A0}", ' ', $aTag->textContent);
        $href = $aTag->getAttribute('href');
        $items = $this->getFormatTwoItems($linkText, $href);

        if (empty($items)) {
            $items[] = [
                'label' => trim($linkText),
                'page'  => '',
                'href'  => $href,
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

    private function getFormatOneItems($textContent, $href)
    {
        $results = [];

        preg_match_all('/(.*?)\.{5,}\s*(\d+)/', $textContent, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $results[] = [
                'label' => trim($match[1]),
                'page'  => (int) $match[2],
                'href'  => $href,
            ];
        }

        return $results;
    }

    private function getFormatTwoItems($textContent, $href)
    {
        $results = [];

        preg_match_all('/^(.*?)\.{5,}\s*([A-Z0-9\- ]{2,})$/', $textContent, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $results[] = [
                'label' => trim($match[1]),
                'page'  => trim($match[2]),
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
