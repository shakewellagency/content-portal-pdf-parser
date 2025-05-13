<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Facades;

use DOMDocument;

class Watermark
{
    public static function add($htmlString, $watermarkText)
    {
        $watermarkText = strtoupper($watermarkText);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlString);
        libxml_clear_errors();

        // Create watermark container
        $watermarkContainer = $dom->createElement('div');
        $watermarkContainer->setAttribute('class', 'watermark-container');
        $watermarkContainer->setAttribute('style', '
            position: absolute;
            top: 1%; 
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            width: 58rem; 
            height: 74rem; 
            text-align: center; 
            display: flex; 
            justify-content: center; 
            align-items: center;'
        );

        // Create watermark content
        $watermarkContent = $dom->createElement('div');
        $watermarkContent->setAttribute('class', 'watermark-content');
        $watermarkContent->setAttribute('style', '
            transform: rotate(-45deg); 
            font-size: 72px;
            color: rgba(255, 0, 0, 0.3);'
        );

        // Add dynamic text to watermark content
        $watermarkContentText = $dom->createTextNode($watermarkText);
        $watermarkContent->appendChild($watermarkContentText);

        // Append watermark content to watermark container
        $watermarkContainer->appendChild($watermarkContent);

        // Find the body element and append the watermark container
        $body = $dom->getElementsByTagName('body')->item(0);
        $body->appendChild($watermarkContainer);

        // Save the modified HTML
        return $dom->saveHTML();
    }

    public static function remove($htmlString)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        $dom->loadHTML($htmlString);
        
        $divs = $dom->getElementsByTagName('div');
        
        foreach ($divs as $div) {
            if ($div->getAttribute('class') === 'watermark-container') {
                $div->parentNode->removeChild($div);
            }
        }
        
        return $dom->saveHTML();
    }
}
