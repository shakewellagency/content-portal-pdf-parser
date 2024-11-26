<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers;

class ContentParserHelper
{
    public static function removeOutline($htmlString, $encode = false)
    {
        $value = preg_replace('/<a name="outline"><\/a><h1>Document Outline<\/h1>\s*<ul>.*<\/ul>/s', '', $htmlString);

        return $encode ? json_encode($value) : $value;
    }

}
