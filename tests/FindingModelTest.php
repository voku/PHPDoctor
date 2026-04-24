<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Baseline\BaselineBuilder;
use voku\PHPDoctor\Baseline\BaselineReader;
use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToFindingMapper;
use voku\PHPDoctor\Finding\Finding;
use voku\PHPDoctor\Finding\FindingCategory;
use voku\PHPDoctor\Finding\FindingFingerprint;
use voku\PHPDoctor\Profile\QualityProfileBuilder;
use voku\PHPDoctor\QualityProfile;

/**
 * @internal
 */
final class FindingModelTest extends \PHPUnit\Framework\TestCase
{
    public function testFindingSerialization(): void
    {
        $message = '[3]: missing property type for voku\tests\SimpleClass->$foo';
        $finding = Finding::fromMessage('test_file.php', $message);

        static::assertSame(
            [
                'file' => 'test_file.php',
                'line' => 3,
                'category' => 'missing_native_type',
                'message' => $message,
                'fingerprint' => \hash(
                    'sha256',
                    \json_encode(
                        [
                            'file' => 'test_file.php',
                            'category' => 'missing_native_type',
                            'line' => 3,
                            'message' => 'missing property type for voku\tests\SimpleClass->$foo',
                        ],
                        \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES
                    )
                ),
            ],
            $finding->toArray()
        );
    }

    public function testFindingPreservesCategory(): void
    {
        $finding = Finding::fromMessage(
            'test_file.php',
            '[39]: foo_broken:39 | Unexpected token "", expected \'}\' at offset 45 on line 1'
        );

        static::assertSame(FindingCategory::PARSE_ERROR, $finding->category()->value());
        static::assertSame(FindingCategory::PARSE_ERROR, $finding->toArray()['category']);
    }

    public function testFindingPreservesFingerprint(): void
    {
        $message = '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()';
        $category = FindingCategory::fromMessage($message);

        $finding = Finding::fromMessage('test_file.php', $message);
        $fingerprint = FindingFingerprint::fromDetails('test_file.php', $category, 8, $message);

        static::assertSame($fingerprint->toString(), $finding->fingerprint()->toString());
    }

    public function testQualityProfileOutputCompatibility(): void
    {
        $errors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
                '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
            ],
        ];

        $typedProfile = QualityProfileBuilder::fromErrors($errors)->toArray();
        $legacyProfile = QualityProfile::fromErrors($errors);

        static::assertSame($legacyProfile, $typedProfile);
    }

    public function testDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
            'test_file.php',
            10,
            ['display_name' => 'voku\tests\OldClass']
        );

        static::assertSame(
            Finding::fromMessage(
                'test_file.php',
                '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass'
            )->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testQualityProfileOutputCompatibilityWithDiagnostics(): void
    {
        $errors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
                '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
            ],
        ];
        $diagnostics = new DiagnosticCollection([
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                'test_file.php',
                10,
                ['display_name' => 'voku\tests\OldClass']
            ),
        ]);

        static::assertSame(
            QualityProfile::fromErrors($errors),
            QualityProfileBuilder::fromErrorsAndDiagnostics($errors, $diagnostics)->toArray()
        );
    }

    public function testBaselineBuilderProducesCompactSchema(): void
    {
        $baseline = BaselineBuilder::fromErrors(
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
                ],
            ]
        )->toArray();

        static::assertSame(1, $baseline['schema_version']);
        static::assertSame('phpdoctor', $baseline['tool']);
        static::assertSame('type_and_phpdoc_quality', $baseline['scope']);
        static::assertIsString($baseline['generated_at']);
        static::assertArrayNotHasKey('summary', $baseline);
        static::assertArrayNotHasKey('new_findings', $baseline);
        static::assertArrayNotHasKey('message', $baseline['findings'][0]);
    }

    public function testBaselineBuilderProducesCompactSchemaWithDiagnostics(): void
    {
        $errors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
                '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
            ],
        ];
        $diagnostics = new DiagnosticCollection([
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                'test_file.php',
                10,
                ['display_name' => 'voku\tests\OldClass']
            ),
        ]);

        $legacyBaseline = BaselineBuilder::fromErrors($errors)->toArray();
        $typedBaseline = BaselineBuilder::fromErrorsAndDiagnostics($errors, $diagnostics)->toArray();

        static::assertSame($legacyBaseline['schema_version'], $typedBaseline['schema_version']);
        static::assertSame($legacyBaseline['tool'], $typedBaseline['tool']);
        static::assertSame($legacyBaseline['scope'], $typedBaseline['scope']);
        static::assertSame($legacyBaseline['findings'], $typedBaseline['findings']);
    }

    public function testBaselineReaderSupportsLegacyAndSchemaVersionOneFormats(): void
    {
        $profile = QualityProfile::fromErrors(
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
                ],
            ]
        );
        $baseline = BaselineBuilder::fromErrors(
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
                ],
            ]
        )->toArray();

        static::assertSame(
            QualityProfile::fingerprintsFromProfile($profile),
            BaselineReader::fromArray($profile)->fingerprints()
        );
        static::assertSame(
            QualityProfile::fingerprintsFromProfile($profile),
            BaselineReader::fromArray($baseline)->fingerprints()
        );
    }
}
