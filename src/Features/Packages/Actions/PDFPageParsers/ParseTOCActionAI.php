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

        $outline = json_decode($rendition->outline);

        libxml_use_internal_errors(true); // handle HTML5 issues safely

        $dom = new DOMDocument();
        $dom->loadHTML($outline);

        $body = $dom->getElementsByTagName('body')->item(0);
        $uls = $body->getElementsByTagName('ul');

        if ($uls->length > 0) {
            // Use optimized approach with proper locking and error handling
            $this->processTocsWithOptimization($rendition, $uls->item(0));
        }

        libxml_clear_errors();
    }

    /**
     * Optimized TOC processing with deadlock prevention and constraint handling
     */
    private function processTocsWithOptimization($rendition, $ul)
    {
        $maxRetries = 3;
        $baseDelay = 100; // milliseconds

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

                    // Safe deletion with proper hierarchy handling
                    $this->safeDeleteExistingTocs($rendition->id);

                    // Extract and create TOCs in proper order
                    $tocStructure = $this->extractTocStructure($ul, $rendition->id);
                    $this->createTocsInBatches($tocStructure);
                });

                // Success - log and return
                Log::info("TOC processing completed successfully for rendition: {$rendition->id}");
                return;

            } catch (\Illuminate\Database\QueryException $e) {
                $this->handleDatabaseException($e, $attempt, $maxRetries, $rendition->id, $baseDelay);
            } catch (\Exception $e) {
                Log::error("Unexpected error processing TOCs for rendition {$rendition->id}: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Handle database exceptions with specific retry logic
     */
    private function handleDatabaseException($e, $attempt, $maxRetries, $renditionId, $baseDelay)
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        $isDeadlock = ($errorCode == 40001 || strpos($errorMessage, 'Deadlock') !== false);
        $isConstraintViolation = ($errorCode == 23000 || strpos($errorMessage, 'foreign key constraint') !== false);
        
        if ($isDeadlock) {
            Log::warning("Deadlock detected on attempt {$attempt} for rendition {$renditionId}. Retrying...");
        } elseif ($isConstraintViolation) {
            Log::warning("Foreign key constraint violation on attempt {$attempt} for rendition {$renditionId}. Retrying...");
        }
        
        if (($isDeadlock || $isConstraintViolation) && $attempt < $maxRetries) {
            // Exponential backoff with jitter
            $delay = $baseDelay * pow(2, $attempt - 1) + rand(0, 50);
            usleep($delay * 1000);
            return; // Continue retry loop
        }
        
        // Re-throw if not retryable or max retries reached
        throw $e;
    }

    /**
     * Safely delete existing TOCs with proper hierarchy handling
     */
    private function safeDeleteExistingTocs($renditionId)
    {
        // Get all existing TOC IDs for this rendition
        $existingTocIds = DB::table('tocs')
            ->where('rendition_id', $renditionId)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($existingTocIds)) {
            return;
        }

        // Delete in proper hierarchical order (children first)
        $deletionOrder = $this->calculateDeletionOrder($existingTocIds);
        
        // Batch soft delete
        foreach (array_chunk($deletionOrder, 100) as $batch) {
            DB::table('tocs')
                ->whereIn('id', $batch)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);
        }

        // Clean up with hard delete
        DB::table('tocs')
            ->where('rendition_id', $renditionId)
            ->whereNotNull('deleted_at')
            ->delete();
    }

    /**
     * Calculate proper deletion order (children before parents)
     */
    private function calculateDeletionOrder($tocIds)
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

        // Build deletion order recursively
        foreach ($tocs as $toc) {
            $this->addTocToDeletionOrder($toc, $tocs, $deletionOrder, $processed);
        }

        return array_reverse($deletionOrder); // Children first
    }

    /**
     * Recursively build deletion order
     */
    private function addTocToDeletionOrder($toc, $allTocs, &$deletionOrder, &$processed)
    {
        if (in_array($toc->id, $processed)) {
            return;
        }

        // Process all children first
        foreach ($allTocs as $childToc) {
            if ($childToc->parent_id === $toc->id) {
                $this->addTocToDeletionOrder($childToc, $allTocs, $deletionOrder, $processed);
            }
        }

        // Then add this TOC
        $deletionOrder[] = $toc->id;
        $processed[] = $toc->id;
    }

    /**
     * Extract TOC structure from DOM efficiently
     */
    private function extractTocStructure($ul, $renditionId)
    {
        $tocStructure = [];
        $orderCounters = [];
        
        $this->extractTocItems($ul, null, $renditionId, $orderCounters, $tocStructure);
        
        return $tocStructure;
    }

    /**
     * Recursively extract TOC items with optimized DOM parsing
     */
    private function extractTocItems($ul, $parentTempId, $renditionId, &$orderCounters, &$tocStructure)
    {
        if (!isset($orderCounters[$parentTempId])) {
            $orderCounters[$parentTempId] = 1;
        }

        foreach ($ul->childNodes as $li) {
            if ($li->nodeName !== 'li') continue;

            // Find anchor tag efficiently
            $aTag = null;
            foreach ($li->childNodes as $node) {
                if ($node->nodeName === 'a') {
                    $aTag = $node;
                    break;
                }
            }

            if (!$aTag) continue;

            // Extract and clean data
            $name = html_entity_decode(trim($aTag->textContent));
            preg_match('/#page(\d+)-div/', $aTag->getAttribute('href'), $pageMatch);
            $pageNo = $pageMatch[1] ?? null;

            // Optimized name cleaning
            $cleanName = $this->cleanTocName($name);

            // Generate unique temporary ID
            $tempId = uniqid('toc_', true);

            $tocItem = [
                'temp_id' => $tempId,
                'name' => $cleanName,
                'page_no' => $pageNo,
                'parent_temp_id' => $parentTempId,
                'rendition_id' => $renditionId,
                'order' => $orderCounters[$parentTempId],
                'children' => []
            ];

            $orderCounters[$parentTempId]++;

            // Process children
            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $this->extractTocItems($child, $tempId, $renditionId, $orderCounters, $tocItem['children']);
                }
            }

            $tocStructure[] = $tocItem;
        }
    }

    /**
     * Optimized name cleaning
     */
    private function cleanTocName($name)
    {
        // Remove non-printable characters
        $cleanName = preg_replace('/[[:^print:]]+/', '', $name);
        $cleanName = trim($cleanName);
        
        // Add spaces between camelCase
        $cleanName = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $cleanName);
        
        return $cleanName;
    }

    /**
     * Create TOCs in batches with proper parent-child relationships
     */
    private function createTocsInBatches($tocStructure)
    {
        $createdTocs = [];
        
        // Create all TOCs level by level to ensure proper parent-child relationships
        $this->createTocLevel($tocStructure, null, $createdTocs);
    }

    /**
     * Create TOC records level by level with batch optimization
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
                // Create TOC with retry logic
                $toc = retry(3, function () use ($payload) {
                    return (new CreateTocAction)->execute($payload);
                }, 50); // Reduced delay for better performance

                if (!$toc || !$toc->id) {
                    Log::error("Failed to create TOC entry after retries.", $payload);
                    continue;
                }

                // Store mapping for children
                $createdTocs[$tocItem['temp_id']] = $toc->id;

                // Create children with actual parent ID
                if (!empty($tocItem['children'])) {
                    $this->createTocLevel($tocItem['children'], $toc->id, $createdTocs);
                }

            } catch (\Exception $e) {
                Log::error("Error creating TOC entry: " . $e->getMessage(), [
                    'payload' => $payload,
                    'exception' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
    }

    /**
     * Legacy method kept for backward compatibility (not used in optimized flow)
     */
    public function processList($ul, $parentId = null, $renditionId, &$orderCounters = [])
    {
        // This method is kept for backward compatibility but not used in the optimized flow
        Log::warning("Legacy processList method called - consider using the optimized flow");
        
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

            $cleanName = $this->cleanTocName($name);

            $payload = [
                'name' => $cleanName,
                'page_no' => $pageNo,
                'parent_id' => $parentId,
                'rendition_id' => $renditionId,
                'order' => $orderCounters[$parentId],
            ];

            $toc = retry(3, function () use ($payload) {
                return (new CreateTocAction)->execute($payload);
            }, 100);

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
