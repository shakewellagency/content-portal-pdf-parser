<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum PublicationStatusEnum: string
{
    use MethodsTrait;

    case Draft = 'draft';

    case Published = 'published';
}
