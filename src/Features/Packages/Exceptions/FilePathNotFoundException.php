<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions;

class FilePathNotFoundException extends \Exception
{
    public function __construct($message = 'File path not found')
    {
        parent::__construct($message);
    }
}
