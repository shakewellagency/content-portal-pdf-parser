<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GenerateHashAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\PDFPageCounterAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\UnlinkTempFileAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PackageInitializationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $package;

    /**
     * Create a new job instance.
     */
    public function __construct($package)
    {
        $this->package = $package;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        //TODO: remove this
        (new UnlinkTempFileAction)->execute();
        //---- remove this

        $packageStatusEnum = config('shakewell-parser.enums.package_status_enum');
        $this->package->status = $packageStatusEnum::Processing->value;
        $this->package->save();

        $this->package = (new GenerateHashAction)->execute($this->package);
        $parserFile = (new GetS3ParserFileTempAction)->execute($this->package);
        $totalPages = (new PDFPageCounterAction)->execute($parserFile);
        $this->package->total_pages = $totalPages;
        $this->package->save();

        unlink($parserFile);
    }
}
