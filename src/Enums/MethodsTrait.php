<?php

namespace Shakewellagency\ContentPortalPdfParser\Enums;

trait MethodsTrait
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function forInValidation(): string
    {
        return implode(',', static::values());
    }

    public static function isValid($value): bool
    {
        return !is_null(self::tryFrom($value));
    }
}
