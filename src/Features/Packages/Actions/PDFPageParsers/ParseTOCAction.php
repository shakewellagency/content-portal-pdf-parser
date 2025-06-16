<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use DOMDocument;
use GuzzleHttp\Promise\Create;
use Shakewellagency\ContentPortalPdfParser\Features\Tocs\Actions\CreateTocAction;

class ParseTOCAction
{
    public function execute($rendition)
    {

        $rendition->refresh();
        
        if (!$rendition->outline) {
            LoggerInfo('No Outline to Parse.', [
                'rendition' => $rendition->toArray(),
            ]);
            return;
        }

        if ($rendition->tocs->isNotEmpty()) {
            $rendition->tocs()->forceDelete();
            $rendition->refresh();
        }
        
        $renditionId = $rendition->id;
        $outline = json_decode($rendition->outline);

        libxml_use_internal_errors(true); // handle HTML5 issues safely

        $dom = new DOMDocument();
        $dom->loadHTML($outline);

        $body = $dom->getElementsByTagName('body')->item(0);

        $uls = $body->getElementsByTagName('ul');
        if ($uls->length > 0) {
            $this->processList($uls->item(0),null, $renditionId);
        }

        libxml_clear_errors();

        
    }

    public function processList($ul, $parentId = null, $renditionId, &$orderCounters = [])
    {
        if (!isset($orderCounters[$parentId])) {
            $orderCounters[$parentId] = 1;
        }

        foreach ($ul->childNodes as $li) {
            if ($li->nodeName !== 'li') continue;

            $aTag = null;
            foreach ($li->childNodes as $node) {
                if ($node->nodeName === 'a') {
                    $aTag = $node;
                    break;
                }
            }

            if (!$aTag) continue;

            $name = html_entity_decode(trim($aTag->textContent));
            preg_match('/#page(\d+)-div/', $aTag->getAttribute('href'), $pageMatch);
            $pageNo = $pageMatch[1] ?? null;

            // Clean up non-printable characters
            $cleanName = preg_replace('/[[:^print:]]+/', '', $name);
            $cleanName = trim($cleanName);
            $cleanName = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $cleanName);

            $payload = [
                'name' => $cleanName,
                'page_no' => $pageNo,
                'parent_id' => $parentId,
                'rendition_id' => $renditionId,
                'order' => $orderCounters[$parentId],
            ];

            $toc = (new CreateTocAction)->execute($payload);

            $orderCounters[$parentId]++;

            // Now look for immediate child <ul> elements under this <li>
            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $this->processList($child, $toc->id, $renditionId, $orderCounters);
                }
            }
        }
    }

}
