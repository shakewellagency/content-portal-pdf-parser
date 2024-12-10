<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions;

use Shakewellagency\ContentPortalPdfParser\Events\ParsingFailedEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions\FilePathNotFoundException;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions\PageCounterException;

class FailedPackageAction
{
    public function execute($package, $version, $exception)
    {
        $errors = [
            FilePathNotFoundException::class,
            PageCounterException::class,
        ];  

        $errorMessage = 'A system error occurred while attempting to parse the package';

        if (in_array(get_class($exception), $errors)) {
            if (get_class($exception) === FilePathNotFoundException::class) {
                $errorMessage =  $exception->getMessage();
            }
        }

        $package->failed_exception = $exception->getMessage();
        $packageStatusEnum = config('shakewell-parser.enums.package_status_enum');
        $package->status = $packageStatusEnum::Failed->value;
        $package->save();

        event(new ParsingFailedEvent($package, $version, $errorMessage));
    }

}
