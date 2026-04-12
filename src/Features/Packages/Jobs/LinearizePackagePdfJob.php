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

    public int $tries = 2;

    public int $timeout = 7200;

    public int $backoff = 60;

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
        if (! Storage::disk($disk)->exists($originalPath)) {
            Log::warning('LinearizePackagePdfJob: S3 object does not exist', [
                'package_id' => $this->packageId,
                'file_path' => $originalPath,
            ]);

            return;
        }

        $tmpIn = $this->createTempFile('pdf_in_');
        $tmpOut = $this->createTempFile('pdf_lin_');
        try {
            $this->streamDownload($disk, $originalPath, $tmpIn);

            if (filesize($tmpIn) === 0) {
                Log::warning('LinearizePackagePdfJob: empty S3 object', [
                    'package_id' => $this->packageId,
                    'file_path' => $originalPath,
                ]);

                return;
            }

            $qpdf->linearizeFile($tmpIn, $tmpOut);

            $key = $this->linearizedKey((string) $originalPath);
            $this->streamUpload($disk, $key, $tmpOut);

            $package->forceFill([
                'linearized_file_path' => $key,
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

    private function streamDownload(string $disk, string $remotePath, string $localPath): void
    {
        $remote = Storage::disk($disk)->readStream($remotePath);
        if ($remote === null || $remote === false) {
            throw new \RuntimeException("Unable to open read stream for {$remotePath}");
        }

        $local = fopen($localPath, 'wb');
        if ($local === false) {
            fclose($remote);
            throw new \RuntimeException("Unable to open local file for writing: {$localPath}");
        }

        try {
            if (stream_copy_to_stream($remote, $local) === false) {
                throw new \RuntimeException("Failed to stream {$remotePath} to {$localPath}");
            }
        } finally {
            if (is_resource($local)) {
                fclose($local);
            }
            if (is_resource($remote)) {
                fclose($remote);
            }
        }
    }

    private function streamUpload(string $disk, string $remotePath, string $localPath): void
    {
        $local = fopen($localPath, 'rb');
        if ($local === false) {
            throw new \RuntimeException("Unable to open local file for reading: {$localPath}");
        }

        try {
            $ok = Storage::disk($disk)->writeStream($remotePath, $local, [
                'ContentType' => 'application/pdf',
            ]);
            if ($ok === false) {
                throw new \RuntimeException("Failed to upload {$localPath} to {$remotePath}");
            }
        } finally {
            if (is_resource($local)) {
                fclose($local);
            }
        }
    }

    private function createTempFile(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary file');
        }

        return $path;
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
