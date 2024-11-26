<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CoverPhotoAction
{
    public function execute($renditionPage, $rendition)
    {
        if ($renditionPage->page !== 1) {
            return;
        }

    }
}
