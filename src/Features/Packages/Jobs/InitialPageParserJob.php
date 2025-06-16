<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\OutlineParseAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\CoverPhotoAction;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers\ParseTOCAction;

class InitialPageParserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    protected $package;
    protected $renditionPage;
    protected $rendition;

    /**
     * For Outline parsing and generating coverphoto for page 1 
     */
    public function __construct($package, $renditionPage, $rendition)
    {
        $renditionPage->refresh();
        $this->package = $package;
        $this->renditionPage = $renditionPage;
        $this->rendition = $rendition;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
       $rendition = (new OutlineParseAction)->execute($this->renditionPage, $this->rendition);
       $rendition->refresh();

       (new CoverPhotoAction)->execute(
            $this->renditionPage, 
            $rendition,
            $this->package
        );

        LoggerInfo('Successfully parsed the 1st page for outline and default coverphoto', [
            'package' => $this->package->toArray(),
            'renditionPage' => $this->renditionPage,
        ]);
    }
}
