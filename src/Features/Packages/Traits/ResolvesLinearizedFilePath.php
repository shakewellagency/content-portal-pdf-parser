<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Traits;

/**
 * Apply to the host package_model. When the `linearized_file_path`
 * column is populated, reads of `file_path` transparently resolve
 * to the linearized object key. Writes remain direct.
 */
trait ResolvesLinearizedFilePath
{
    public function getFilePathAttribute(?string $value): ?string
    {
        $linearized = $this->attributes['linearized_file_path'] ?? null;

        return ! empty($linearized) ? $linearized : $value;
    }

    public function getOriginalFilePath(): ?string
    {
        return $this->attributes['file_path'] ?? null;
    }
}
