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

class PDFPageParserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $page;
    protected $totalPage;
    protected $package;
    protected $rendition;
    protected $version;
    protected $parserFile;
    protected $localEnv;
    protected $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $page, 
        $totalPage, 
        $package,
        $parserFile,
        $cacheKey
    ){
        $this->package = $package;
        $this->page = $page;
        $this->totalPage = $totalPage;
        $this->rendition = $package->rendition;
        $this->version = $this->rendition->version;
        $this->parserFile = $parserFile;
        $this->cacheKey = $cacheKey;
        $this->localEnv = config('shakewell-parser.env') == 'local';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {

        if (Cache::get($this->cacheKey)) {
            return;
        }

        $renditionPage = $this->createRenditionPage();
        
        $parserFile = $this->localEnv ? $this->parserFile : (new GetS3ParserFileTempAction)->execute($this->package);

        $renditionPage = (new PDFPageParserAction)->execute(
            $this->page,
            $parserFile,
            $renditionPage,
            $this->package,
        );

        if ($this->page == 1) {
            InitialPageParserJob::dispatch(
                $this->package, 
                $renditionPage, 
                $this->rendition
            );
        }

        (new PageAssetDataIDAction)->execute($renditionPage);

        if ($this->package->total_pages == $this->page) {
            $this->finisher();
        }
        
        if (!$this->localEnv) {
            unlink($parserFile);
        }
        
        Log::info("DONE Parsing page: {$this->page}");
    }

    private function createRenditionPage()
    {
        $parameter = [
            'page_no' => $this->page,
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
        
        if ($this->localEnv) {
            unlink($this->parserFile);
        }

        LoggerInfo('Successfully parsed the PDF', [
            'package' => $this->package,
        ]);

        event(new ParsingFinishedEvent($this->package, $this->version));
    }

    public function failed(Throwable $exception)
    {
        Cache::forever($this->cacheKey, true);

        (new FailedPackageAction)->execute(
            $this->package, 
            $this->version, 
            $exception
        );

        $this->rendition->delete();
    }
}
