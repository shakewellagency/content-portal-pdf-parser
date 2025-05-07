<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;


class PageFontColorATagAction
{
    /**
     * This Action will;
     * 1. identify all font-family and change it to sans-serif or serif
     * 2. add a tag a "{ color: black; text-decoration: none; }" at the end of style 
     * 3. identify the class with color: #0000ff and add class at the end of style
     * ".ft05 a { color: #0000ff !important; text-decoration: none !important;}"
     */
    public function execute($renditionPage)
    {
        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);

        $body = $dom->getElementsByTagName('body')->item(0);

        $styleTagsInBody = [];

        if ($body) {
            foreach ($body->getElementsByTagName('style') as $styleTag) {
                $styleTagsInBody[] = $styleTag->nodeValue;
            }
        }

        if ($body) {
            $this->processStyle($body, $styleTagsInBody);
        }

        $modified = $dom->saveHTML();
        $renditionPage->content = json_encode($modified);
        $renditionPage->save();
        $renditionPage->refresh();

        return $renditionPage;
    }

    private function processStyle($body, $styleTagsInBody): void
    {
        $fontClassifications = $this->classifyFontFamilies($styleTagsInBody);
    
        // Loop through all the style tags
        foreach ($body->getElementsByTagName('style') as $styleTag) {
            $updatedStyle = $styleTag->nodeValue;

            // replacing the font-family 
            foreach ($fontClassifications as $originalFont => $classification) {
                $pattern = '/font-family:[^;]*' . preg_quote($originalFont, '/') . '([^;]*);/i';
                $replacement = 'font-family: ' . $classification . ';';
                $updatedStyle = preg_replace($pattern, $replacement, $updatedStyle);
            }

            // Identify classes with color: #0000ff and append the necessary rule for <a> tag
            preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{[^}]*color:\s*#0000ff;/', $updatedStyle, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $className) {
                    // Append the new rule for <a> tag with !important
                    $newRule = '.' . $className . ' a {color: #0000ff !important; text-decoration: none !important;}';
                    $updatedStyle .= "\n" . $newRule;
                }
            }

            // Append the existing a tag rule at the end of the style tag
            $updatedStyle .= "\na {\n    color: black;\n    text-decoration: none;\n}";

            // Add a comment to indicate that this HTML has been processed
            $updatedStyle .= "\n/* font_color_processed=true */";

            // Update the style tag content with the modified style
            $styleTag->nodeValue = $updatedStyle;
        }
    }

    private function classifyFontFamilies(array $styleContents): array
    {
        $serifFonts = ['Times', 'Georgia', 'Garamond', 'Cambria'];
        $sansSerifFonts = ['Arial', 'ArialMT', 'Helvetica', 'Calibri', 'Verdana'];

        $result = [];

        foreach ($styleContents as $css) {
            preg_match_all('/font-family:([^;]+);/', $css, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $fontFamilyValue) {
                    $fonts = array_map('trim', explode(',', $fontFamilyValue));

                    foreach ($fonts as $font) {
                        $font = trim($font, "\"'");

                        if (collect($serifFonts)->first(fn($f) => Str::contains($font, $f))) {
                            $result[$font] = 'serif';
                        } elseif (collect($sansSerifFonts)->first(fn($f) => Str::contains($font, $f))) {
                            $result[$font] = 'sans-serif';
                        } else {
                            $result[$font] = 'unknown';
                        }
                    }
                }
            }
        }

        return $result;
    }
}
