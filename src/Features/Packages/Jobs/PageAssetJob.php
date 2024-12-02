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
use Shakewellagency\ContentPortalPdfParser\Enums\PackageStatusEnum;
use Shakewellagency\ContentPortalPdfParser\Models\RenditionAsset;

class PageAssetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $renditionPage;

    /**
     * Create a new job instance.
     */
    public function __construct($renditionPage)
    {
        $this->renditionPage = $renditionPage;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $htmlString = json_decode($this->renditionPage->content);
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlString);

        foreach ($dom->getElementsByTagName('img') as $img) {
            $originalSrc = $img->getAttribute('src');
            preg_match('/\/output\/([^?]+)/', $originalSrc, $matches);

            if (isset($matches[1])) {
                $originalSrc = $matches[1];
            }

            $renditionAsset = RenditionAsset::where('file_name', $originalSrc)->first();
            if ($renditionAsset) {
                $img->setAttribute('asset-path', $renditionAsset->file_path);
            }
        }

        $modified = $dom->saveHTML();
        $this->renditionPage->content = json_encode($modified);
        $this->renditionPage->save();
        $this->renditionPage->refresh();

        return $this->renditionPage;
    }
}
