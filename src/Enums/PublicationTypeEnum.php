<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum PublicationTypeEnum: string
{
    use MethodsTrait;

    case Agile = 'agile';

    case ExternalLink = 'external_link';

    case UploadFile = 'upload_file';
}
