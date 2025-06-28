<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shakewellagency\ContentPortalPdfParser\Features\Tocs\Actions\CreateTocAction;

class ParseTOCAction
{
    public function execute($rendition, $package)
    {
        $rendition->refresh();

        if (!$rendition->outline) {
            LoggerInfo("package:$package->id - No Outline to Parse.", [
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
            DB::transaction(function () use ($uls, $renditionId) {
                $orderCounters = [];
                $this->processList($uls->item(0), null, $renditionId, $orderCounters);
            }, 3); // 3 retry attempts on deadlock
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
            $href = $aTag->getAttribute('href');

            preg_match('/#page(\d+)-div|rendition\/[a-f0-9-]+\/rendition-page\/(\d+)/', $href, $pageMatch);
            $pageNo = $pageMatch[1] ?? $pageMatch[2] ?? null;

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

            // Use retry around CreateTocAction to handle deadlocks
            $toc = retry(3, function () use ($payload) {
                return (new CreateTocAction)->execute($payload);
            }, 100); // 3 retries, 100ms delay between

            if (!$toc->id) {
                Log::error("Failed to create TOC entry after retries.", $payload);
                continue;
            }

            $orderCounters[$parentId]++;

            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $this->processList($child, $toc->id, $renditionId, $orderCounters);
                }
            }
        }
    }

}
