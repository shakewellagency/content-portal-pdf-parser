<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Str;

class ExtractHeightWidthAction
{
    /**
     * This Action will;
     * 1. extract page height, width and unit
     */
    public function execute($renditionPage) 
    {
        $htmlString = json_decode($renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);
    
        $div = $dom->getElementById("page{$renditionPage->page_no}-div");
        
        if (!$div) {
            $div = $dom->getElementById("page{$renditionPage->page_no}");
        }
        
        if ($div) {
            $style = $div->getAttribute('style');
    
            // Use regex to extract width and height values with units
            preg_match('/width\s*:\s*(\d+)([a-z%]*)/', $style, $widthMatch);
            preg_match('/height\s*:\s*(\d+)([a-z%]*)/', $style, $heightMatch);
    
            $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : null;
            $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : null;
            $unit = isset($widthMatch[2]) ? $widthMatch[2] : null;
    
            // For debug / display purposes:
            logger()->info("Width: {$width}{$unit}, Height: {$height}{$unit}");
    
            // Example return array
            $result = [
                'width' => $width,
                'height' => $height,
                'unit' => $unit
            ];
        } else {
            // Div not found
            $result = [
                'width' => null,
                'height' => null,
                'unit' => null
            ];
        }

        $renditionPage->height = $result['height'];
        $renditionPage->width = $result['width'];
        $renditionPage->unit = $result['unit'];
        $renditionPage->save();
        $renditionPage->refresh();
    
        return $renditionPage;
    }
}
