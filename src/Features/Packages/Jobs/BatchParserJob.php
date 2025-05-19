<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\PageAssetDataIDAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\PDFPageParserAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\SetVersionCurrentAction;
use Shakewellagency\ContentPortalPdfParser\Features\RenditionPages\Actions\CreateRenditionPageAction;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingFinishedEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\FailedPackageAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PackageInitializes\GetS3ParserFileTempAction;
use Throwable;
use Illuminate\Support\Facades\Cache;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\ExtractHeightWidthAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\HTMLCleanUps;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\PageDeepLinkAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\PageFontColorATagAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\ParseContentValueAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\RemoveLastHRTagAction;

class BatchParserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $totalPage;
    protected $package;
    protected $pageRange;
    protected $cacheKey;

    protected $rendition;
    protected $version;

    /**
     * Create a new job instance.
     */
    public function __construct($package, $totalPage, $pageRange, $cacheKey)
    {

        $this->package = $package;
        $this->totalPage = $totalPage;
        $this->pageRange = $pageRange;
        $this->cacheKey = $cacheKey;
        $this->rendition = $package->rendition;
        $this->version = $this->rendition->version;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        
        if (Cache::get($this->cacheKey)) {
            return;
        }

        [$startPage, $endPage] = $this->pageRange;

        LoggerInfo("Start Parsing Batch from: {$startPage} to: {$endPage}", [
            'package' => $this->package->toArray(),
            'rendition' => $this->rendition,
        ]);
        Log::info("Start Parsing Batch from: {$startPage} to: {$endPage}");


        $parserFile = (new GetS3ParserFileTempAction)->execute($this->package);

        for ($page = $startPage; $page <= $endPage; $page++) {

            $renditionPage = $this->createRenditionPage($page);
            $renditionPage = (new PDFPageParserAction)->execute(
                $page,
                $parserFile,
                $renditionPage,
                $this->package,
            );


            if ($page == 1) {
                InitialPageParserJob::dispatch(
                    $this->package, 
                    $renditionPage, 
                    $this->rendition
                );
            }

            (new PageAssetDataIDAction)->execute($renditionPage);
            (new PageDeepLinkAction)->execute($renditionPage);
            (new PageFontColorATagAction)->execute($renditionPage);
            (new ExtractHeightWidthAction)->execute($renditionPage);
            (new HTMLCleanUps)->execute($renditionPage);
            (new ParseContentValueAction)->execute($renditionPage);
            $renditionPage->refresh();

            Log::info("DONE Parsing Page {$page}");
        }


        $totalParsedPage = $this->rendition->renditionPages->where('is_parsed', 1)->count();

        if ($this->package->total_pages == $totalParsedPage) {
            LoggerInfo("DONE Parsing All Pages", [
                'package' => $this->package->toArray(),
                'rendition' => $this->rendition,
            ]);
            $this->finisher();
        }

        unlink($parserFile);

        Log::info("DONE Parsing Batch from: {$startPage} to: {$endPage}");
        LoggerInfo("DONE Parsing Batch from: {$startPage} to: {$endPage}", [
            'package' => $this->package->toArray(),
            'rendition' => $this->rendition,
        ]);
    }

    private function createRenditionPage($page)
    {
        $parameter = [
            'page_no' => $page,
            'rendition_id' => $this->rendition->id,
        ];
        
        return (new CreateRenditionPageAction)->execute($parameter);
    }

    private function finisher()
    {
        
        $packageStatusEnum = config('shakewell-parser.enums.package_status_enum');
        $this->package->finished_at = Carbon::now();
        $this->package->status = $packageStatusEnum::Finished->value;
        $this->package->save();
        $this->rendition->is_parsed = true;
        $this->rendition->save();
        $this->version->is_parsed = true;
        $this->version->save();

        (new SetVersionCurrentAction)->execute($this->version, $this->rendition);

        LoggerInfo('Successfully parsed the PDF', [
            'package' => $this->package,
        ]);

        event(new ParsingFinishedEvent($this->package, $this->version));
    }

    public function failed(Throwable $exception)
    {
        if (Cache::get($this->cacheKey)) {
            return;
        }

        Cache::put($this->cacheKey, true, now()->addDay());

        (new FailedPackageAction)->execute(
            $this->package, 
            $this->version, 
            $exception
        );

        $this->rendition->delete();
        $this->rendition->renditionPages()->delete();
    }
}