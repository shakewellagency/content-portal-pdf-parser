<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

enum PackageStatusEnum: string
{
    use MethodsTrait;

    case Queued = 'queued';

    case Processing = 'processing';

    case Finished = 'finished';

    case Failed = 'failed';

}
