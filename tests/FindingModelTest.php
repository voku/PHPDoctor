<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Baseline\BaselineBuilder;
use voku\PHPDoctor\Baseline\BaselineFlow;
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

    public function testFindingCategoryRejectsUnknownValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported finding category "not_a_real_category".');

        FindingCategory::fromValue('not_a_real_category');
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

    public function testParseErrorDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $message = '/tmp/parser.php:370 | Syntax error, unexpected \'{\', expecting T_VARIABLE'
            . "\n"
            . '/tmp/parser.php on line 2';
        $diagnostic = new Diagnostic(
            DiagnosticId::PARSER_SYNTAX_ERROR,
            '',
            null,
            ['legacy_message' => $message]
        );

        static::assertSame(
            Finding::fromMessage('', $message)->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testMissingNativePropertyTypeDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::MISSING_NATIVE_PROPERTY_TYPE,
            'test_file.php',
            3,
            [
                'display_name' => 'voku\tests\SimpleClass',
                'property_name' => 'foo',
                'declaring_class' => 'voku\tests\SimpleClass',
                'symbol' => 'voku\tests\SimpleClass->$foo',
            ]
        );

        static::assertSame(
            Finding::fromMessage(
                'test_file.php',
                '[3]: missing property type for voku\tests\SimpleClass->$foo'
            )->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testMissingNativeParameterTypeDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::MISSING_NATIVE_PARAMETER_TYPE,
            'test_file.php',
            8,
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingParameterType()',
                'function_or_method_name' => 'missingParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\SimpleClass->missingParameterType() | parameter:value',
            ]
        );

        static::assertSame(
            Finding::fromMessage(
                'test_file.php',
                '[8]: missing parameter type for voku\tests\SimpleClass->missingParameterType() | parameter:value'
            )->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testMissingNativeReturnTypeDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::MISSING_NATIVE_RETURN_TYPE,
            'test_file.php',
            6,
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingReturnType()',
                'function_or_method_name' => 'missingReturnType',
                'kind' => 'method',
                'symbol' => 'voku\tests\SimpleClass->missingReturnType()',
            ]
        );

        static::assertSame(
            Finding::fromMessage(
                'test_file.php',
                '[6]: missing return type for voku\tests\SimpleClass->missingReturnType()'
            )->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testMissingPhpDocParameterTypeDiagnosticToFindingPreservesLegacyCompatibility(): void
    {
        $diagnostic = new Diagnostic(
            DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE,
            'test_file.php',
            8,
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingPhpDocParameterType()',
                'function_or_method_name' => 'missingPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter_phpdoc',
                'missing_type' => 'null',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value',
            ]
        );

        static::assertSame(
            Finding::fromMessage(
                'test_file.php',
                '[8]: missing parameter type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value'
            )->toArray(),
            DiagnosticToFindingMapper::map($diagnostic)->toArray()
        );
    }

    public function testAnalysisResultFindingsAvoidDuplicateDeprecatedMethodFinding(): void
    {
        $message = '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass->oldMethod()';
        $diagnostic = new Diagnostic(
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
            'test_file.php',
            10,
            ['display_name' => 'voku\tests\OldClass->oldMethod()']
        );

        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([$diagnostic])
        );
        $findings = $analysisResult->findings();

        static::assertCount(1, $findings);
        static::assertSame(
            Finding::fromMessage('test_file.php', $message)->toArray(),
            $findings[0]->toArray()
        );
    }

    public function testAnalysisResultFindingsAvoidDuplicateParseErrorFinding(): void
    {
        $message = '/tmp/parser.php:370 | Syntax error, unexpected \'{\', expecting T_VARIABLE'
            . "\n"
            . '/tmp/parser.php on line 2';
        $diagnostic = new Diagnostic(
            DiagnosticId::PARSER_SYNTAX_ERROR,
            '',
            null,
            ['legacy_message' => $message]
        );

        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([$diagnostic])
        );
        $findings = $analysisResult->findings();

        static::assertCount(1, $findings);
        static::assertSame(
            Finding::fromMessage('', $message)->toArray(),
            $findings[0]->toArray()
        );
    }

    public function testQualityProfileOutputCompatibilityFromAnalysisResult(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                    'test_file.php',
                    10,
                    ['display_name' => 'voku\tests\OldClass']
                ),
            ]),
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ]
        );

        static::assertSame(
            QualityProfile::fromErrors($analysisResult->toLegacyErrors()),
            QualityProfileBuilder::fromAnalysisResult($analysisResult)->toArray()
        );
    }

    public function testQualityProfileBuilderFromAnalysisResultMatchesLegacyProjection(): void
    {
        $legacyOnlyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
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
        $analysisResult = new AnalysisResult($diagnostics, $legacyOnlyErrors);

        static::assertSame(
            QualityProfileBuilder::fromErrors($analysisResult->toLegacyErrors())->toArray(),
            QualityProfileBuilder::fromAnalysisResult($analysisResult)->toArray()
        );
    }

    public function testQualityProfileFacadeFromAnalysisResultMatchesLegacyProjection(): void
    {
        $legacyOnlyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
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
        $analysisResult = new AnalysisResult($diagnostics, $legacyOnlyErrors);

        static::assertSame(
            QualityProfile::fromErrors($analysisResult->toLegacyErrors()),
            QualityProfile::fromAnalysisResult($analysisResult)
        );
    }

    public function testQualityProfileBaselineSuppressionWorksWithDeprecatedMethodDiagnostics(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                    'test_file.php',
                    10,
                    ['display_name' => 'voku\tests\OldClass->oldMethod()']
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame([], $profile['new_findings']);
    }

    public function testQualityProfileBaselineSuppressionWorksWithParseErrorDiagnostics(): void
    {
        $message = '/tmp/parser.php:370 | Syntax error, unexpected \'{\', expecting T_VARIABLE'
            . "\n"
            . '/tmp/parser.php on line 2';
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::PARSER_SYNTAX_ERROR,
                    '',
                    null,
                    ['legacy_message' => $message]
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame([], $profile['new_findings']);
    }

    public function testQualityProfileBaselineSuppressionWorksWithMissingNativePropertyTypeDiagnostics(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::MISSING_NATIVE_PROPERTY_TYPE,
                    'test_file.php',
                    3,
                    [
                        'display_name' => 'voku\tests\SimpleClass',
                        'property_name' => 'foo',
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'symbol' => 'voku\tests\SimpleClass->$foo',
                    ]
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame([], $profile['new_findings']);
    }

    public function testQualityProfileBaselineSuppressionWorksWithMissingNativeParameterTypeDiagnostics(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::MISSING_NATIVE_PARAMETER_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->missingParameterType()',
                        'function_or_method_name' => 'missingParameterType',
                        'parameter_name' => 'value',
                        'kind' => 'method_parameter',
                        'parameter_position' => 0,
                        'symbol' => 'voku\tests\SimpleClass->missingParameterType() | parameter:value',
                    ]
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame([], $profile['new_findings']);
    }

    public function testQualityProfileBaselineSuppressionWorksWithMissingNativeReturnTypeDiagnostics(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::MISSING_NATIVE_RETURN_TYPE,
                    'test_file.php',
                    6,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->missingReturnType()',
                        'function_or_method_name' => 'missingReturnType',
                        'kind' => 'method',
                        'symbol' => 'voku\tests\SimpleClass->missingReturnType()',
                    ]
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame([], $profile['new_findings']);
    }

    public function testQualityProfileBaselineSuppressionWorksWithMissingPhpDocParameterTypeDiagnostics(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->missingPhpDocParameterType()',
                        'function_or_method_name' => 'missingPhpDocParameterType',
                        'parameter_name' => 'value',
                        'kind' => 'method_parameter_phpdoc',
                        'missing_type' => 'null',
                        'parameter_position' => 0,
                        'symbol' => 'voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value',
                    ]
                ),
            ])
        );
        $baseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        $profile = QualityProfileBuilder::fromAnalysisResult(
            $analysisResult,
            BaselineReader::fromArray($baseline)->fingerprints()
        )->toArray();

        static::assertSame(1, $profile['total_error_count']);
        static::assertSame(0, $profile['new_error_count']);
        static::assertSame(1, $profile['summary']['missing_phpdoc_type']);
        static::assertSame(0, $profile['new_summary']['missing_phpdoc_type']);
        static::assertSame([], $profile['new_findings']);
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

    public function testBaselineBuilderProducesCompactSchemaFromAnalysisResult(): void
    {
        $analysisResult = new AnalysisResult(
            new DiagnosticCollection([
                new Diagnostic(
                    DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                    'test_file.php',
                    10,
                    ['display_name' => 'voku\tests\OldClass']
                ),
            ]),
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ]
        );

        $legacyBaseline = BaselineBuilder::fromErrors($analysisResult->toLegacyErrors())->toArray();
        $typedBaseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

        static::assertSame($legacyBaseline['schema_version'], $typedBaseline['schema_version']);
        static::assertSame($legacyBaseline['tool'], $typedBaseline['tool']);
        static::assertSame($legacyBaseline['scope'], $typedBaseline['scope']);
        static::assertSame($legacyBaseline['findings'], $typedBaseline['findings']);
    }

    public function testBaselineBuilderFromAnalysisResultMatchesLegacyProjection(): void
    {
        $legacyOnlyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
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
        $analysisResult = new AnalysisResult($diagnostics, $legacyOnlyErrors);

        static::assertSame(
            BaselineBuilder::fromErrors($analysisResult->toLegacyErrors())->toArray(),
            BaselineBuilder::fromAnalysisResult($analysisResult)->toArray()
        );
    }

    public function testBaselineFlowGenerateFromAnalysisResultWritesSameCompactSchema(): void
    {
        $legacyOnlyErrors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
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
        $analysisResult = new AnalysisResult($diagnostics, $legacyOnlyErrors);
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-analysis-result-baseline-');
        static::assertIsString($baselineFile);

        try {
            BaselineFlow::generateFromAnalysisResult($baselineFile, $analysisResult);

            $generatedBaseline = \json_decode((string) \file_get_contents($baselineFile), true);
            $expectedBaseline = BaselineBuilder::fromAnalysisResult($analysisResult)->toArray();

            static::assertIsArray($generatedBaseline);
            static::assertSame($expectedBaseline['schema_version'], $generatedBaseline['schema_version'] ?? null);
            static::assertSame($expectedBaseline['tool'], $generatedBaseline['tool'] ?? null);
            static::assertSame($expectedBaseline['scope'], $generatedBaseline['scope'] ?? null);
            static::assertSame($expectedBaseline['findings'], $generatedBaseline['findings'] ?? null);
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testAnalysisResultFindingsAvoidDuplicateTypedDiagnosticProjection(): void
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
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ]
        );

        static::assertCount(2, $analysisResult->findings());
        static::assertSame(
            QualityProfile::fromErrors($analysisResult->toLegacyErrors()),
            QualityProfileBuilder::fromAnalysisResult($analysisResult)->toArray()
        );
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
