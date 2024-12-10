<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Shakewellagency\ContentPortalPdfParser\Features\Renditions\Actions\CreateRenditionAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\FailedPackageAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Throwable;
use Illuminate\Support\Facades\Bus;

class PageParserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $package;
    protected $version;
    protected $rendition;

    /**
     * Create a new job instance.
     */
    public function __construct($package, $version)
    {
        $package->refresh();
        $this->package = $package;
        $this->version = $version;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $parserFile = (new GetS3ParserFileTempAction)->execute($this->package);
        $this->rendition = $this->createRendition();
        
        LoggerInfo('Successfully created rendition', [
            'package' => $this->package->toArray(),
            'rendition' => $this->rendition,
        ]);

        $totalPages = $this->package->total_pages;
        
        for ($page = 1; $page <= $totalPages; $page++) {
            $chain[] = new PDFPageParserJob(
                $page, 
                $totalPages, 
                $this->package, 
                $parserFile
            );
        }

        Bus::chain($chain)->dispatch();

    }

    private function createRendition()
    {
        $parameter = [
            'version_id' => $this->version->id,
            'package_id' => $this->package->id,
            'type' => $this->package->file_type,
        ];
        $this->package->refresh();
        return (new CreateRenditionAction)->execute($parameter);
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
