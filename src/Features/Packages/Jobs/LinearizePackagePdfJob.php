<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Services\QpdfLinearizeService;

class LinearizePackagePdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(
        public string $packageId
    ) {}

    public function handle(QpdfLinearizeService $qpdf): void
    {
        if (! config('shakewell-parser.linearize_on_parse', true)) {
            Log::warning('LinearizePackagePdfJob: linearize_on_parse is disabled');

            return;
        }

        $class = config('shakewell-parser.models.package_model');
        if (! $class || ! class_exists($class)) {
            Log::warning('LinearizePackagePdfJob: package_model not configured');

            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $package */
        $package = $class::query()->find($this->packageId);
        if (! $package || ! empty($package->getAttributes()['linearized_file_path'] ?? null)) {
            Log::warning('LinearizePackagePdfJob: package not found or already linearized');

            return;
        }

        $type = strtolower((string) $package->getAttribute('file_type'));
        if (! in_array($type, ['pdf', 'application/pdf'], true)) {
            Log::warning('LinearizePackagePdfJob: file type is not PDF');

            return;
        }

        $originalPath = $package->getAttributes()['file_path'] ?? null;
        if (! $originalPath) {
            Log::warning('LinearizePackagePdfJob: file path is not set');

            return;
        }

        if (! $qpdf->isAvailable()) {
            Log::warning('LinearizePackagePdfJob skipped: qpdf not available', [
                'package_id' => $this->packageId,
            ]);

            return;
        }

        $disk = config('shakewell-parser.s3');
        $contents = Storage::disk($disk)->get($originalPath);
        if ($contents === null || $contents === '') {
            Log::warning('LinearizePackagePdfJob: empty or missing S3 object', [
                'package_id' => $this->packageId,
                'file_path' => $originalPath,
            ]);

            return;
        }

        $tmpIn = tempnam(sys_get_temp_dir(), 'pdf_in_').'.pdf';
        $tmpOut = tempnam(sys_get_temp_dir(), 'pdf_lin_').'.pdf';
        try {
            file_put_contents($tmpIn, $contents);
            $qpdf->linearizeFile($tmpIn, $tmpOut);

            $key = $this->linearizedKey((string) $originalPath);
            Storage::disk($disk)->put($key, file_get_contents($tmpOut), [
                'ContentType' => 'application/pdf',
            ]);

            $package->forceFill([
                'linearized_file_path' => $key,
                'pdf_viewer_v2' => true,
            ])->save();

            Log::info('LinearizePackagePdfJob: successfully linearized package', [
                'package_id' => $this->packageId,
                'linearized_file_path' => $key,
            ]);
        } catch (\Throwable $e) {
            Log::error('LinearizePackagePdfJob failed', [
                'package_id' => $this->packageId,
                'exception' => $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);

            Log::info('LinearizePackagePdfJob: successfully cleaned up temporary files', [
                'package_id' => $this->packageId,
                'tmp_in' => $tmpIn,
                'tmp_out' => $tmpOut,
            ]);
        }
    }

    private function linearizedKey(string $originalKey): string
    {
        $originalKey = ltrim($originalKey, '/');
        $dir = dirname($originalKey);
        $base = pathinfo($originalKey, PATHINFO_FILENAME);
        $ext = pathinfo($originalKey, PATHINFO_EXTENSION) ?: 'pdf';

        $suffix = '_linearized.'.strtolower($ext);

        return $dir !== '.' && $dir !== '' ? $dir.'/'.$base.$suffix : $base.$suffix;
    }
}
