<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Str;

class AdjustPaddingTopAction
{

    public function execute($dom) 
    {
        $styleTags = $dom->getElementsByTagName('style');
        foreach ($styleTags as $styleTag) {
            $css = $styleTag->nodeValue;

            // Check if p { exists in this style tag
            if (preg_match('/p\s*\{[^}]+\}/', $css)) {
                $css = preg_replace_callback('/p\s*\{([^}]+)\}/', function ($matches) {
                    $styleContent = $matches[1];
                    if (strpos($styleContent, 'padding-top') === false) {
                        $styleContent .= ' padding-top: 2px;';
                    }
                    return "p { $styleContent }";
                }, $css);

                // Update the style node
                $styleTag->nodeValue = $css;
            }
        }

        // 2️⃣ Update all <p> with style containing top:13px to top:10px
        $pTags = $dom->getElementsByTagName('p');
        foreach ($pTags as $pTag) {
            $style = $pTag->getAttribute('style');
            if (strpos($style, 'top:13px') !== false) {
                $newStyle = str_replace('top:13px', 'top:10px', $style);
                $pTag->setAttribute('style', $newStyle);
            }
        }

        return $dom;
    }
}
