<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;

/**
 * @internal
 */
final class AnalysisResultTest extends \PHPUnit\Framework\TestCase
{
    public function testAnalysisResultReturnsDiagnostics(): void
    {
        $diagnostics = new DiagnosticCollection([
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                'test_file.php',
                10,
                ['display_name' => 'voku\tests\OldClass']
            ),
        ]);

        $analysisResult = new AnalysisResult($diagnostics);

        static::assertSame($diagnostics, $analysisResult->diagnostics());
    }

    public function testAnalysisResultToLegacyErrorsPreservesLegacyTextOutput(): void
    {
        $diagnostics = new DiagnosticCollection([
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                'test_file.php',
                10,
                ['display_name' => 'voku\tests\OldClass']
            ),
        ]);
        $legacyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\OldClass->$value',
                '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
            ],
        ];

        $analysisResult = new AnalysisResult($diagnostics, $legacyErrors);

        static::assertSame($legacyErrors, $analysisResult->toLegacyErrors());
    }
}
