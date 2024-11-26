<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PDFPageCounterAction
{
    public function execute($parserFile)
    {
        $process = new Process(['pdfinfo', $parserFile]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $output = $process->getOutput();
        preg_match('/Pages:\s+(\d+)/', $output, $matches);
        $totalPages = isset($matches[1]) ? (int)$matches[1] : 0;

        return $totalPages;
    }

}
