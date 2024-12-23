<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class DocToPdfConverterHelper
{
    public static function convert($package, string $fileContent, string $currentTimestamp): string
    {
        try {
            $pdfContent = Pdf::loadHTML($fileContent)->output();
            $pdfPath = sys_get_temp_dir() . "/parser_file_{$package->hash}_{$currentTimestamp}.pdf";

            if (! is_writable(sys_get_temp_dir())) {
                throw new Exception("Temporary directory is not writable.");
            }

            $fileSaved = file_put_contents($pdfPath, $pdfContent);

            if ($fileSaved === false) {
                throw new Exception("Failed to save PDF file to: {$pdfPath}");
            }

            return $pdfPath;
        } catch (Exception $e) {
            error_log("PDF conversion error: " . $e->getMessage());
            throw $e;
        }
    }
}
