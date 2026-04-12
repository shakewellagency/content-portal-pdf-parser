<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Shakewellagency\ContentPortalPdfParser\Events\ParsingTriggerEvent;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades\PDFParse;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\LinearizePackagePdfJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PackageInitializationJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\PageParserJob;

class PDFParsePackage extends Model
{
    protected $table = 'packages';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';
}

function makePackage(): PDFParsePackage
{
    return PDFParsePackage::create(['id' => 'pkg-42']);
}

beforeEach(function () {
    Bus::fake();
    Event::fake();

    Schema::create('packages', function ($table) {
        $table->string('id')->primary();
    });
});

afterEach(function () {
    Schema::dropIfExists('packages');
});

it('dispatches ParsingTriggerEvent before queueing the chain', function () {
    $package = makePackage();

    PDFParse::execute($package, 'v1');

    Event::assertDispatched(ParsingTriggerEvent::class);
});

it('chains linearize after package initialization and page parsing when enabled', function () {
    config(['shakewell-parser.linearize_on_parse' => true]);

    $package = makePackage();

    PDFParse::execute($package, 'v1');

    Bus::assertChained([
        PackageInitializationJob::class,
        PageParserJob::class,
        LinearizePackagePdfJob::class,
    ]);
});

it('omits linearize from the chain when disabled', function () {
    config(['shakewell-parser.linearize_on_parse' => false]);

    $package = makePackage();

    PDFParse::execute($package, 'v1');

    Bus::assertChained([
        PackageInitializationJob::class,
        PageParserJob::class,
    ]);

    Bus::assertNotDispatched(LinearizePackagePdfJob::class);
});
