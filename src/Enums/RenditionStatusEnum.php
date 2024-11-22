<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum RenditionStatusEnum: string
{
    use MethodsTrait;

    case Image = 'image';

    case Video = 'video';

    case Audio = 'audio';
}
