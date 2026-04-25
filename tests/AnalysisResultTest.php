<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToFindingMapper;
use voku\PHPDoctor\Finding\Finding;

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
        $legacyOnlyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\OldClass->$value',
            ],
        ];

        $analysisResult = new AnalysisResult($diagnostics, $legacyOnlyErrors);

        static::assertSame($legacyOnlyErrors, $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\OldClass->$value',
                    '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalysisResultFindingsReturnsOneFindingForDiagnosticOnlyError(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
            'test_file.php',
            10,
            ['display_name' => 'voku\tests\OldClass']
        );
        $analysisResult = new AnalysisResult(new DiagnosticCollection([$diagnostic]));

        static::assertSame(
            [DiagnosticToFindingMapper::map($diagnostic)->toArray()],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalysisResultFindingsReturnsOneFindingForLegacyOnlyError(): void
    {
        $analysisResult = new AnalysisResult(
            DiagnosticCollection::empty(),
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\OldClass->$value',
                ],
            ]
        );

        static::assertSame(
            [
                Finding::fromMessage(
                    'test_file.php',
                    '[3]: missing property type for voku\tests\OldClass->$value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalysisResultFindingsReturnsTwoFindingsForLegacyOnlyAndDiagnosticErrors(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
            'test_file.php',
            10,
            ['display_name' => 'voku\tests\OldClass']
        );
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([$diagnostic]),
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\OldClass->$value',
                ],
            ]
        );

        static::assertSame(
            [
                Finding::fromMessage(
                    'test_file.php',
                    '[3]: missing property type for voku\tests\OldClass->$value'
                )->toArray(),
                DiagnosticToFindingMapper::map($diagnostic)->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }
}
