<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum RenditionTypeEnum: string
{
    use MethodsTrait;

    case PDF = 'pdf';

    case Docs = 'docs';

    case XML = 'xml';
}
