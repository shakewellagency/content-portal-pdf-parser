<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Facades;

use DOMDocument;

class Watermark
{
    public static function add($htmlString, $watermarkModel)
    {
        // Handle string watermark for backward compatibility
        if (is_string($watermarkModel)) {
            // Create a simple object with text type properties
            $textWatermark = new \stdClass();
            $textWatermark->type = 'text';
            $textWatermark->value = $watermarkModel;
            $watermarkModel = $textWatermark;
        }

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

        // Get rotation value from model or default to -45deg
        $rotation = $watermarkModel->rotate ?? -45;
        // Get opacity value from model or default to 0.3
        $opacity = $watermarkModel->opacity ?? 0.3;

        // Handle different watermark types
        if ($watermarkModel->type === 'text') {
            // For text watermarks
            $watermarkText = strtoupper($watermarkModel->value);
            $fontSize = $watermarkModel->font_size ?? 72;
            $color = $watermarkModel->color ?? 'rgba(255, 0, 0, ' . $opacity . ')';

            $watermarkContent->setAttribute('style', '
                transform: rotate(' . $rotation . 'deg);
                font-size: ' . $fontSize . 'px;
                color: ' . $color . ';'
            );

            // Add dynamic text to watermark content
            $watermarkContentText = $dom->createTextNode($watermarkText);
            $watermarkContent->appendChild($watermarkContentText);
        } else if ($watermarkModel->type === 'image') {
            // For image watermarks

            $watermarkContent->setAttribute('style', '
                transform: rotate(' . $rotation . 'deg);'
            );

            // Create image element
            $imageElement = $dom->createElement('img');
            $imageElement->setAttribute('src', $watermarkModel->image_url);
            $imageElement->setAttribute('style', '
                opacity: ' . $opacity . ';
                max-width: 29rem; /* 50% of container width */
                max-height: 37rem; /* 50% of container height */
                width: auto;
                height: auto;'
            );

            // Add image to watermark content
            $watermarkContent->appendChild($imageElement);
        }

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
