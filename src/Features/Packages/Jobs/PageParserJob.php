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
        $this->rendition = $this->createRendition();

        $totalPages = $this->package->total_pages;

        $cacheKey = 'job_chain_failure_flag-'. Str::random(10);
        Cache::forget($cacheKey);

        $batchSize = 100;

        for ($startPage = 1; $startPage <= $totalPages; $startPage += $batchSize) {
            $endPage = min($startPage + $batchSize - 1, $totalPages);
            BatchParserJob::dispatch($this->package->id, $this->rendition->id, $totalPages, [$startPage, $endPage], $cacheKey);
        }
    }

    private function createRendition()
    {
        $parameter = [
            'version_id' => $this->version->id,
            'package_id' => $this->package->id,
            'type' => $this->package->file_type,
        ];

        $renditionModel = config('shakewell-parser.models.rendition_model');
        $rendition = $renditionModel::where($parameter)->first();

        $this->package->refresh();

        if ($rendition) {
            LoggerInfo("package:{$this->package->id} - Fetched existing rendition", [
                'package' => $this->package->toArray(),
                'rendition' => $this->rendition,
            ]);

            return $rendition;
        }

        LoggerInfo("package:{$this->package->id} - Successfully created rendition", [
            'package' => $this->package->toArray(),
            'rendition' => $this->rendition,
        ]);

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
