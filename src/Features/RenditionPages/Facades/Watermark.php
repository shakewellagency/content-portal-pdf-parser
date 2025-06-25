<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Facades;

use DOMDocument;

class Watermark
{
    /**
     * Insert the watermark on the page content
     *
     * @param string $htmlString
     * @param string | App\Features\Watermarks\Models\Watermark $watermark
     * @return string
     */
    public static function add($htmlString, $watermark)
    {
        $watermark = self::handleStringWatermarks($watermark);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlString);
        libxml_clear_errors();

        $watermarkContainer = self::createWatermarkContainer($dom);
        $watermarkContent = self::createWatermarkContent($dom, $watermark);

        $watermarkContainer->appendChild($watermarkContent);
        $dom->getElementsByTagName('body')->item(0)->appendChild($watermarkContainer);

        return $dom->saveHTML();
    }

    protected static function handleStringWatermarks($model)
    {
        if (is_string($model)) {
            $textWatermark = new \stdClass();
            $textWatermark->type = 'text';
            $textWatermark->value = $model;

            return $textWatermark;
        }

        return $model;
    }

    protected static function createWatermarkContainer($dom)
    {
        $container = $dom->createElement('div');
        $container->setAttribute('class', 'watermark-container');
        $container->setAttribute('style', '
            position: absolute;
            top: 1%;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            height: 74rem;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;'
        );

        return $container;
    }

    protected static function createWatermarkContent($dom, $model)
    {
        $content = $dom->createElement('div');
        $content->setAttribute('class', 'watermark-content');

        if ($model->type === 'text') {
            return self::processTextWatermark($dom, $content, $model);
        }

        if ($model->type === 'image') {
            return self::processImageWatermark($dom, $content, $model);
        }

        return $content;
    }

    protected static function processTextWatermark($dom, $content, $model)
    {
        $rotation = $model->rotate ?? -45;
        $opacity = $model->opacity ?? 0.3;
        $fontSize = $model->font_size ?? 72;
        $color = $model->color ?? 'rgba(255, 0, 0, ' . $opacity . ')';
        $text = strtoupper($model->value);

        $content->setAttribute('style', '
            transform: rotate(' . $rotation . 'deg);
            font-size: ' . $fontSize . 'px;
            color: ' . $color . ';'
        );

        $textNode = $dom->createTextNode($text);
        $content->appendChild($textNode);

        return $content;
    }

    protected static function processImageWatermark($dom, $content, $model)
    {
        $rotation = $model->rotate ?? -45;
        $opacity = $model->opacity ?? 0.3;

        $content->setAttribute('style', '
            transform: rotate(' . $rotation . 'deg);'
        );

        $image = $dom->createElement('img');
        $image->setAttribute('src', $model->image_url);
        $image->setAttribute('style', '
            opacity: ' . $opacity . ';
            max-width: 29rem;
            max-height: 37rem;
            width: auto;
            height: auto;'
        );

        $content->appendChild($image);

        return $content;
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
