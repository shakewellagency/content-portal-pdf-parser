<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shakewellagency\ContentPortalPdfParser\Features\Tocs\Actions\CreateTocAction;

class ParseTOCActionAI
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

        $renditionId = $rendition->id;
        $outline = json_decode($rendition->outline);

        libxml_use_internal_errors(true); // handle HTML5 issues safely

        $dom = new DOMDocument();
        $dom->loadHTML($outline);

        $body = $dom->getElementsByTagName('body')->item(0);
        $uls = $body->getElementsByTagName('ul');

        if ($uls->length > 0) {
            // Use a more robust approach with proper locking and error handling
            $this->processTocsWithLocking($rendition, $uls->item(0));
        }

        libxml_clear_errors();
    }

    /**
     * Process TOCs with proper locking to prevent deadlocks and constraint violations
     */
    private function processTocsWithLocking($rendition, $ul)
    {
        $maxRetries = 3;
        $retryDelay = 100; // milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                DB::transaction(function () use ($rendition, $ul) {
                    // Lock the rendition to prevent concurrent modifications
                    $lockedRendition = DB::table('renditions')
                        ->where('id', $rendition->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedRendition) {
                        throw new \Exception("Rendition not found or locked by another process");
                    }

                    // Safe deletion strategy: soft delete first, then process
                    $this->safeDeleteExistingTocs($rendition->id);

                    // Process the TOC structure in two passes to handle parent-child relationships
                    $tocStructure = $this->extractTocStructure($ul, $rendition->id);
                    $this->createTocsInOrder($tocStructure);
                });

                // If we reach here, transaction succeeded
                Log::info("TOC processing completed successfully for rendition: {$rendition->id}");
                return;

            } catch (\Illuminate\Database\QueryException $e) {
                $errorCode = $e->getCode();
                
                if ($errorCode == 40001 || strpos($e->getMessage(), 'Deadlock') !== false) {
                    // Deadlock detected
                    Log::warning("Deadlock detected on attempt {$attempt} for rendition {$rendition->id}. Retrying...");
                    
                    if ($attempt < $maxRetries) {
                        usleep($retryDelay * 1000 * $attempt); // Exponential backoff
                        continue;
                    }
                } elseif ($errorCode == 23000 || strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    // Foreign key constraint violation
                    Log::error("Foreign key constraint violation for rendition {$rendition->id}: " . $e->getMessage());
                    
                    if ($attempt < $maxRetries) {
                        usleep($retryDelay * 1000 * $attempt);
                        continue;
                    }
                }
                
                // Re-throw if not a retryable error or max retries reached
                throw $e;
            } catch (\Exception $e) {
                Log::error("Unexpected error processing TOCs for rendition {$rendition->id}: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Safely delete existing TOCs to prevent deadlocks
     */
    private function safeDeleteExistingTocs($renditionId)
    {
        // First, get all existing TOC IDs for this rendition
        $existingTocIds = DB::table('tocs')
            ->where('rendition_id', $renditionId)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($existingTocIds)) {
            return;
        }

        // Delete in reverse hierarchical order (children first, then parents)
        // This prevents foreign key constraint violations during deletion
        $deletionOrder = $this->getTocsInDeletionOrder($existingTocIds);
        
        foreach ($deletionOrder as $tocId) {
            DB::table('tocs')
                ->where('id', $tocId)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);
        }

        // Force delete after soft delete to clean up completely
        DB::table('tocs')
            ->where('rendition_id', $renditionId)
            ->whereNotNull('deleted_at')
            ->delete();
    }

    /**
     * Get TOCs in proper deletion order (children first)
     */
    private function getTocsInDeletionOrder($tocIds)
    {
        if (empty($tocIds)) {
            return [];
        }

        $tocs = DB::table('tocs')
            ->whereIn('id', $tocIds)
            ->whereNull('deleted_at')
            ->select('id', 'parent_id')
            ->get()
            ->keyBy('id');

        $deletionOrder = [];
        $processed = [];

        // Process children first, then parents
        foreach ($tocs as $toc) {
            $this->addTocToDeletionOrder($toc, $tocs, $deletionOrder, $processed);
        }

        return array_reverse($deletionOrder); // Reverse to get children-first order
    }

    /**
     * Recursively add TOC to deletion order
     */
    private function addTocToDeletionOrder($toc, $allTocs, &$deletionOrder, &$processed)
    {
        if (in_array($toc->id, $processed)) {
            return;
        }

        // First, process all children
        foreach ($allTocs as $childToc) {
            if ($childToc->parent_id === $toc->id) {
                $this->addTocToDeletionOrder($childToc, $allTocs, $deletionOrder, $processed);
            }
        }

        // Then add this TOC to deletion order
        $deletionOrder[] = $toc->id;
        $processed[] = $toc->id;
    }

    /**
     * Extract TOC structure from DOM in a hierarchical format
     */
    private function extractTocStructure($ul, $renditionId)
    {
        $tocStructure = [];
        $orderCounters = [];
        
        $this->extractTocItems($ul, null, $renditionId, $orderCounters, $tocStructure);
        
        return $tocStructure;
    }

    /**
     * Recursively extract TOC items from DOM
     */
    private function extractTocItems($ul, $parentId, $renditionId, &$orderCounters, &$tocStructure)
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

            // Clean name string
            $cleanName = preg_replace('/[[:^print:]]+/', '', $name);
            $cleanName = trim($cleanName);
            $cleanName = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $cleanName);

            // Generate a temporary ID for this item
            $tempId = uniqid('temp_toc_', true);

            $tocItem = [
                'temp_id' => $tempId,
                'name' => $cleanName,
                'page_no' => $pageNo,
                'parent_id' => $parentId,
                'rendition_id' => $renditionId,
                'order' => $orderCounters[$parentId],
                'children' => []
            ];

            $orderCounters[$parentId]++;

            // Process children <ul> under this <li>
            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $this->extractTocItems($child, $tempId, $renditionId, $orderCounters, $tocItem['children']);
                }
            }

            $tocStructure[] = $tocItem;
        }
    }

    /**
     * Create TOCs in proper order to handle parent-child relationships
     */
    private function createTocsInOrder($tocStructure)
    {
        $createdTocs = [];
        
        // First pass: Create all parent records
        $this->createTocLevel($tocStructure, null, $createdTocs);
    }

    /**
     * Recursively create TOC records level by level
     */
    private function createTocLevel($tocItems, $actualParentId, &$createdTocs)
    {
        foreach ($tocItems as $tocItem) {
            $payload = [
                'name' => $tocItem['name'],
                'page_no' => $tocItem['page_no'],
                'parent_id' => $actualParentId,
                'rendition_id' => $tocItem['rendition_id'],
                'order' => $tocItem['order'],
            ];

            try {
                // Create the TOC record with retry logic for constraint violations
                $toc = retry(3, function () use ($payload) {
                    return (new CreateTocAction)->execute($payload);
                }, 100);

                if (!$toc || !$toc->id) {
                    Log::error("Failed to create TOC entry after retries.", $payload);
                    continue;
                }

                // Store the mapping between temp_id and actual ID
                $createdTocs[$tocItem['temp_id']] = $toc->id;

                // Now create children with the actual parent ID
                if (!empty($tocItem['children'])) {
                    $this->createTocLevel($tocItem['children'], $toc->id, $createdTocs);
                }

            } catch (\Exception $e) {
                Log::error("Error creating TOC entry: " . $e->getMessage(), $payload);
                continue;
            }
        }
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

            // Clean name string
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

            // Now process children <ul> under this <li>
            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $this->processList($child, $toc->id, $renditionId, $orderCounters);
                }
            }
        }
    }
}
