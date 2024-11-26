<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum RenditionAssetTypeEnum: string
{
    use MethodsTrait;

    case Image = 'image';

    case Video = 'video';

    case Audio = 'audio';
}
