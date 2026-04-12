<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs\LinearizePackagePdfJob;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Services\QpdfLinearizeService;

class FakePackageModel extends Model
{
    protected $table = 'packages';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';
}

beforeEach(function () {
    config(['shakewell-parser.models.package_model' => FakePackageModel::class]);
    config(['shakewell-parser.s3' => 'test-disk']);
    Storage::fake('test-disk');

    Schema::create('packages', function ($table) {
        $table->string('id')->primary();
        $table->string('file_type')->nullable();
        $table->text('file_path')->nullable();
        $table->text('linearized_file_path')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('packages');
});

it('does nothing and logs when linearize_on_parse is disabled', function () {
    config(['shakewell-parser.linearize_on_parse' => false]);
    Log::spy();

    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('missing'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->with('LinearizePackagePdfJob: linearize_on_parse is disabled')
        ->once();
});

it('returns early when the package cannot be found', function () {
    Log::spy();
    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('missing-id'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->with('LinearizePackagePdfJob: package not found or already linearized')
        ->once();
});

it('returns early when the file type is not a PDF', function () {
    FakePackageModel::create([
        'id' => 'pkg-1',
        'file_type' => 'image/png',
        'file_path' => 'packages/pkg-1/file.png',
    ]);
    Log::spy();
    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('pkg-1'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->with('LinearizePackagePdfJob: file type is not PDF')
        ->once();
});

it('returns early when file_path is not set', function () {
    FakePackageModel::create([
        'id' => 'pkg-2',
        'file_type' => 'pdf',
        'file_path' => null,
    ]);
    Log::spy();
    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('pkg-2'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->with('LinearizePackagePdfJob: file path is not set')
        ->once();
});

it('returns early when qpdf is not available', function () {
    FakePackageModel::create([
        'id' => 'pkg-3',
        'file_type' => 'pdf',
        'file_path' => 'packages/pkg-3/file.pdf',
    ]);
    Storage::disk('test-disk')->put('packages/pkg-3/file.pdf', 'fake pdf contents');
    Log::spy();

    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldReceive('isAvailable')->andReturn(false);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('pkg-3'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($msg) => $msg === 'LinearizePackagePdfJob skipped: qpdf not available')
        ->once();
});

it('linearizes the pdf, uploads it, and stores the linearized_file_path', function () {
    FakePackageModel::create([
        'id' => 'pkg-4',
        'file_type' => 'application/pdf',
        'file_path' => 'packages/pkg-4/document.pdf',
    ]);
    Storage::disk('test-disk')->put('packages/pkg-4/document.pdf', 'original-bytes');

    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldReceive('isAvailable')->andReturn(true);
    $qpdf->shouldReceive('linearizeFile')
        ->once()
        ->andReturnUsing(function (string $in, string $out) {
            file_put_contents($out, 'linearized-bytes');
        });

    (new LinearizePackagePdfJob('pkg-4'))->handle($qpdf);

    Storage::disk('test-disk')->assertExists('packages/pkg-4/document_linearized.pdf');
    expect(Storage::disk('test-disk')->get('packages/pkg-4/document_linearized.pdf'))
        ->toBe('linearized-bytes');

    $fresh = FakePackageModel::find('pkg-4');
    expect($fresh->linearized_file_path)->toBe('packages/pkg-4/document_linearized.pdf');
});

it('skips packages that are already linearized', function () {
    FakePackageModel::create([
        'id' => 'pkg-5',
        'file_type' => 'pdf',
        'file_path' => 'packages/pkg-5/doc.pdf',
        'linearized_file_path' => 'packages/pkg-5/doc_linearized.pdf',
    ]);
    Log::spy();

    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldNotReceive('linearizeFile');

    (new LinearizePackagePdfJob('pkg-5'))->handle($qpdf);

    Log::shouldHaveReceived('warning')
        ->with('LinearizePackagePdfJob: package not found or already linearized')
        ->once();
});

it('cleans up both temp files even when linearization throws', function () {
    FakePackageModel::create([
        'id' => 'pkg-6',
        'file_type' => 'pdf',
        'file_path' => 'packages/pkg-6/doc.pdf',
    ]);
    Storage::disk('test-disk')->put('packages/pkg-6/doc.pdf', 'bytes');

    $capturedIn = null;
    $capturedOut = null;

    $qpdf = Mockery::mock(QpdfLinearizeService::class);
    $qpdf->shouldReceive('isAvailable')->andReturn(true);
    $qpdf->shouldReceive('linearizeFile')
        ->once()
        ->andReturnUsing(function (string $in, string $out) use (&$capturedIn, &$capturedOut) {
            $capturedIn = $in;
            $capturedOut = $out;
            throw new RuntimeException('qpdf exploded');
        });

    (new LinearizePackagePdfJob('pkg-6'))->handle($qpdf);

    expect($capturedIn)->not->toBeNull()
        ->and($capturedOut)->not->toBeNull()
        ->and(file_exists($capturedIn))->toBeFalse()
        ->and(file_exists($capturedOut))->toBeFalse();
});
