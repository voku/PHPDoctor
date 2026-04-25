<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Autoload\ComposerAutoloaderLoader;

/**
 * @internal
 */
final class ComposerAutoloaderLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function testRequireOnceIfNeededLoadsNewAutoloader(): void
    {
        $autoloadClass = 'ComposerAutoloaderInit' . \bin2hex(\random_bytes(16));
        [$directory, $autoloadFile] = $this->createComposerAutoloadFixture($autoloadClass);

        try {
            unset($GLOBALS['phpdoctor_autoload_loader_included']);

            static::assertTrue(ComposerAutoloaderLoader::requireOnceIfNeeded($autoloadFile));
            static::assertSame(1, $GLOBALS['phpdoctor_autoload_loader_included'] ?? 0);
        } finally {
            $this->cleanupFixture($directory);
        }
    }

    public function testRequireOnceIfNeededSkipsAlreadyLoadedAutoloader(): void
    {
        $autoloadClass = 'ComposerAutoloaderInit' . \bin2hex(\random_bytes(16));
        [$directory, $autoloadFile] = $this->createComposerAutoloadFixture($autoloadClass);

        try {
            unset($GLOBALS['phpdoctor_autoload_loader_included']);
            eval('class ' . $autoloadClass . ' {}');

            static::assertFalse(ComposerAutoloaderLoader::requireOnceIfNeeded($autoloadFile));
            static::assertSame(0, $GLOBALS['phpdoctor_autoload_loader_included'] ?? 0);
        } finally {
            $this->cleanupFixture($directory);
        }
    }

    /**
     * @return array{string, string}
     */
    private function createComposerAutoloadFixture(string $autoloadClass): array
    {
        do {
            $directory = \sys_get_temp_dir() . '/phpdoctor-composer-autoload-' . \bin2hex(\random_bytes(8));
        } while (\file_exists($directory));

        static::assertTrue(\mkdir($directory));
        static::assertTrue(\mkdir($directory . '/vendor'));
        static::assertTrue(\mkdir($directory . '/vendor/composer'));

        \file_put_contents(
            $directory . '/vendor/composer/autoload_real.php',
            "<?php\n\nclass $autoloadClass\n{\n}\n"
        );
        \file_put_contents(
            $directory . '/vendor/autoload.php',
            "<?php\n\n\$GLOBALS['phpdoctor_autoload_loader_included'] = (\$GLOBALS['phpdoctor_autoload_loader_included'] ?? 0) + 1;\nreturn true;\n"
        );

        return [$directory, $directory . '/vendor/autoload.php'];
    }

    private function cleanupFixture(string $directory): void
    {
        foreach ([
            '/vendor/composer/autoload_real.php',
            '/vendor/autoload.php',
        ] as $file) {
            if (\is_file($directory . $file)) {
                \unlink($directory . $file);
            }
        }

        foreach ([
            '/vendor/composer',
            '/vendor',
            '',
        ] as $suffix) {
            if (\is_dir($directory . $suffix)) {
                \rmdir($directory . $suffix);
            }
        }
    }
}
