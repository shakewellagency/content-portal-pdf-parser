<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class QpdfLinearizeService
{
    public function linearizeFile(string $sourceAbsolutePath, string $destinationAbsolutePath): void
    {
        if (! is_readable($sourceAbsolutePath)) {
            throw new RuntimeException("Source PDF is not readable: {$sourceAbsolutePath}");
        }

        $binary = $this->binaryPath();
        $process = new Process([$binary, '--linearize', $sourceAbsolutePath, $destinationAbsolutePath]);
        $process->setTimeout((int) config('shakewell-parser.qpdf_timeout_seconds', 7200));
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('qpdf linearize failed', [
                'command' => $process->getCommandLine(),
                'exit_code' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);

            throw new RuntimeException(
                'qpdf failed: '.$process->getErrorOutput()
            );
        }
    }

    public function binaryPath(): string
    {
        $configured = (string) config('shakewell-parser.qpdf_binary', 'qpdf');
        if ($configured !== 'qpdf' && is_executable($configured)) {
            return $configured;
        }

        $found = (new ExecutableFinder)->find('qpdf', null, ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin']);

        return $found ?? 'qpdf';
    }

    public function isAvailable(): bool
    {
        try {
            $p = new Process([$this->binaryPath(), '--version']);
            $p->run();

            return $p->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
