<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Baseline;

final class BaselineWriter
{
    public static function write(string $file, Baseline $baseline): bool
    {
        return \file_put_contents($file, self::jsonEncode($baseline) . "\n") !== false;
    }

    public static function jsonEncode(Baseline $baseline): string
    {
        return \json_encode(
            $baseline->toArray(),
            \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES
        );
    }
}
