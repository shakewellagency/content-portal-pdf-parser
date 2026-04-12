<?php

use Illuminate\Database\Eloquent\Model;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Traits\ResolvesLinearizedFilePath;

class FakePackage extends Model
{
    use ResolvesLinearizedFilePath;

    protected $guarded = [];
}

it('returns original file_path when no linearized_file_path is set', function () {
    $package = new FakePackage([
        'file_path' => 'packages/abc/original.pdf',
    ]);

    expect($package->file_path)->toBe('packages/abc/original.pdf');
});

it('returns linearized_file_path when it is set', function () {
    $package = new FakePackage([
        'file_path' => 'packages/abc/original.pdf',
        'linearized_file_path' => 'packages/abc/original_linearized.pdf',
    ]);

    expect($package->file_path)->toBe('packages/abc/original_linearized.pdf');
});

it('returns original file_path when linearized_file_path is empty string', function () {
    $package = new FakePackage([
        'file_path' => 'packages/abc/original.pdf',
        'linearized_file_path' => '',
    ]);

    expect($package->file_path)->toBe('packages/abc/original.pdf');
});

it('exposes the original file_path via getOriginalFilePath', function () {
    $package = new FakePackage([
        'file_path' => 'packages/abc/original.pdf',
        'linearized_file_path' => 'packages/abc/original_linearized.pdf',
    ]);

    expect($package->getOriginalFilePath())->toBe('packages/abc/original.pdf');
});

it('returns null when no file paths are set', function () {
    $package = new FakePackage;

    expect($package->file_path)->toBeNull()
        ->and($package->getOriginalFilePath())->toBeNull();
});
