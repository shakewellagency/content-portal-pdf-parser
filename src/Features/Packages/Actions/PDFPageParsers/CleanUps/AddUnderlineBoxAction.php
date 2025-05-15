<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CleanUps;

use Illuminate\Support\Str;

class AddUnderlineBoxAction
{

public function execute($dom) 
{
    $body = $dom->getElementsByTagName('body')->item(0);

    foreach ($body->getElementsByTagName('style') as $styleTag) {
        $styleContent = $styleTag->nodeValue;

        // Find all class names with color: #0000ff;
        preg_match_all('/(\.([a-zA-Z0-9_-]+)\s*\{[^}]*color:\s*#0000ff;[^}]*\})/', $styleContent, $matches, PREG_OFFSET_CAPTURE);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $fullRule = $match[0];

                if (strpos($fullRule, 'background-color: #fff;') !== false) {
                    continue;  // Skip if the background-color style is already added
                }

                // Append your desired styles before the closing }
                $updatedRule = preg_replace(
                    '/\}/',
                    " background-color: #fff; text-decoration: underline; text-underline-offset: 3.5px; }",
                    $fullRule,
                    1
                );

                // Replace the original rule with the updated one
                $styleContent = str_replace($fullRule, $updatedRule, $styleContent);
            }

            // Update the <style> tag content
            $styleTag->nodeValue = $styleContent;
        }
    }

    return $dom;
}


}
