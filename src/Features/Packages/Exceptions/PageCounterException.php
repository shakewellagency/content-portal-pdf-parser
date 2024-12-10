<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions;

class PageCounterException extends \Exception
{
    public function __construct($message = 'A system error occurred while parsing the page count.')
    {
        parent::__construct($message);
    }
}
