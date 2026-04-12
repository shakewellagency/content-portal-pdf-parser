<?php

use Shakewellagency\ContentPortalPdfParser\Features\Packages\Services\QpdfLinearizeService;

it('throws when the source file is not readable', function () {
    $service = new QpdfLinearizeService;
    $missing = sys_get_temp_dir().'/does_not_exist_'.uniqid().'.pdf';

    expect(fn () => $service->linearizeFile($missing, sys_get_temp_dir().'/out.pdf'))
        ->toThrow(RuntimeException::class);
});

it('returns the configured binary when it is an executable absolute path', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'fake_qpdf_');
    chmod($tmp, 0755);
    config(['shakewell-parser.qpdf_binary' => $tmp]);

    $service = new QpdfLinearizeService;

    try {
        expect($service->binaryPath())->toBe($tmp);
    } finally {
        @unlink($tmp);
    }
});

it('falls back to ExecutableFinder when the configured value is the bare name', function () {
    config(['shakewell-parser.qpdf_binary' => 'qpdf']);

    $service = new QpdfLinearizeService;

    expect($service->binaryPath())->toBeString();
});

it('reports unavailable when the binary cannot be executed', function () {
    config(['shakewell-parser.qpdf_binary' => '/definitely/not/a/real/path/qpdf']);

    $service = new QpdfLinearizeService;

    expect($service->isAvailable())->toBeFalse();
});
