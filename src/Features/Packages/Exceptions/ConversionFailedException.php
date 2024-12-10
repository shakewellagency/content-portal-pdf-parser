<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions;

class ConversionFailedException extends \Exception
{
    public function __construct($message = 'A system error occurred while parsing the pdf page.')
    {
        parent::__construct($message);
    }
}
