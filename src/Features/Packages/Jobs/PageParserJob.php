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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
    protected $parserFile;

    /**
     * Create a new job instance.
     */
    public function __construct($package, $version, $parserFile)
    {
        $package->refresh();
        $this->package = $package;
        $this->version = $version;
        $this->parserFile = $parserFile;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->rendition = $this->createRendition();
        
        LoggerInfo('Successfully created rendition', [
            'package' => $this->package->toArray(),
            'rendition' => $this->rendition,
        ]);

        $totalPages = $this->package->total_pages;

        
        $cacheKey = 'job_chain_failure_flag-'. Str::random(10);
        Cache::forget($cacheKey);

        $chunkSize = 100;

        for ($page = 1; $page <= $totalPages; $page++) {
            $jobs[] = new PDFPageParserJob(
                $page,
                $totalPages,
                $this->package,
                $this->parserFile,
                $cacheKey
            );
        }

        $chunks = array_chunk($jobs, $chunkSize);

        foreach ($chunks as $chunk) {
            Bus::chain($chunk)->dispatch();
        }
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
