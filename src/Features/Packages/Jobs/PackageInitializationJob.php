<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Shakewellagency\ContentPortalPdfParser\Events\ParsingFailedEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\FailedPackageAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GenerateHashAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\PDFPageCounterAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\UnlinkTempFileAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingStartedEvent;
use Throwable;

class PackageInitializationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $package;
    protected $version;
    protected $parserFile;

    /**
     * Create a new job instance.
     */
    public function __construct($package, $version, $parserFile)
    {
        $this->package = $package;
        $this->version = $version;
        $this->parserFile = $parserFile;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        event(new ParsingStartedEvent($this->package, $this->version));
        
        $packageStatusEnum = config('shakewell-parser.enums.package_status_enum');
        $this->package->status = $packageStatusEnum::Processing->value;
        $this->package->save();
       
        $this->package = (new GenerateHashAction)->execute($this->package);
        $totalPages = (new PDFPageCounterAction)->execute($this->parserFile);
        $this->package->total_pages = $totalPages;
        $this->package->save();

        LoggerInfo('Successfully initialized the package', [
            'package' => $this->package,
            'version' => $this->version,
            'parserFile' => $this->parserFile,
        ]);
    }

    public function failed(Throwable $exception)
    {
        (new FailedPackageAction)->execute(
            $this->package, 
            $this->version, 
            $exception
        );
    }
}
