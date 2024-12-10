<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions\PageCounterException;
use Symfony\Component\Process\Process;

class PDFPageCounterAction
{
    public function execute($parserFile)
    {
        $process = new Process(['pdfinfo', $parserFile]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PageCounterException('A system error occurred while parsing the page count.');
        }
        $output = $process->getOutput();
        preg_match('/Pages:\s+(\d+)/', $output, $matches);
        $totalPages = isset($matches[1]) ? (int)$matches[1] : 0;

        return $totalPages;
    }

}
