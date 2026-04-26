<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Baseline\BaselineBuilder;
use voku\PHPDoctor\Baseline\BaselineFlow;
use voku\PHPDoctor\Baseline\BaselineFlowException;

/**
 * @internal
 */
final class BaselineFlowTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateAndLoadFingerprintsRoundTrip(): void
    {
        $errors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
                '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
            ],
        ];
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-baseline-flow-');
        static::assertIsString($baselineFile);

        try {
            BaselineFlow::generate($baselineFile, $errors);

            static::assertSame(
                BaselineBuilder::fromErrors($errors)->fingerprints(),
                BaselineFlow::loadFingerprints($baselineFile)
            );
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testLoadFingerprintsRejectsInvalidJson(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-invalid-baseline-');
        static::assertIsString($baselineFile);
        static::assertNotFalse(\file_put_contents($baselineFile, '{invalid-json'));

        $this->expectException(BaselineFlowException::class);
        $this->expectExceptionMessage('does not contain valid JSON');

        try {
            BaselineFlow::loadFingerprints($baselineFile);
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testGenerateRejectsMissingDirectory(): void
    {
        $baselineFile = \sys_get_temp_dir() . '/phpdoctor-missing-' . \bin2hex(\random_bytes(8)) . '/baseline.json';

        $this->expectException(BaselineFlowException::class);
        $this->expectExceptionMessage('directory');
        $this->expectExceptionMessage('does not exist');

        BaselineFlow::generate($baselineFile, []);
    }
}
