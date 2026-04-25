<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Autoload;

final class ComposerAutoloaderLoader
{
    public static function requireOnceIfNeeded(string $autoloadFile): bool
    {
        $composerAutoloaderClass = self::findComposerAutoloaderClass($autoloadFile);
        if ($composerAutoloaderClass !== null && \class_exists($composerAutoloaderClass, false)) {
            return false;
        }

        /** @noinspection PhpIncludeInspection */
        require_once $autoloadFile;

        return true;
    }

    private static function findComposerAutoloaderClass(string $autoloadFile): ?string
    {
        $autoloadRealPath = \dirname($autoloadFile) . '/composer/autoload_real.php';
        if (!\is_file($autoloadRealPath)) {
            return null;
        }

        $autoloadRealContents = \file_get_contents($autoloadRealPath);
        if (!\is_string($autoloadRealContents)) {
            return null;
        }

        \preg_match('/class\s+(ComposerAutoloaderInit[a-f0-9]+)\b/i', $autoloadRealContents, $matches);

        return $matches[1] ?? null;
    }
}
