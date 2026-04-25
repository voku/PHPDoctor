<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToFindingMapper;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;
use voku\PHPDoctor\Finding\FindingFingerprint;

/**
 * @internal
 */
final class DiagnosticMappingTest extends \PHPUnit\Framework\TestCase
{
    public function testDiagnosticMappingCasesCoverEveryDiagnosticId(): void
    {
        $mappedIds = \array_keys(self::diagnosticMappingCases());
        $diagnosticIds = \array_values((new \ReflectionClass(DiagnosticId::class))->getConstants());

        \sort($mappedIds);
        \sort($diagnosticIds);

        static::assertSame($diagnosticIds, $mappedIds);
    }

    /**
     * @dataProvider diagnosticMappingCases
     *
     * @param array{
     *     diagnostic: Diagnostic,
     *     legacy_message: string,
     *     category: string
     * } $case
     */
    public function testDiagnosticMappingsStayCompatible(array $case): void
    {
        $diagnostic = $case['diagnostic'];
        $legacyMessage = $case['legacy_message'];
        $finding = DiagnosticToFindingMapper::map($diagnostic);

        static::assertSame($legacyMessage, DiagnosticToLegacyMessageMapper::map($diagnostic));
        static::assertSame($case['category'], $finding->category()->value());
        static::assertSame(
            [
                'file' => $diagnostic->file(),
                'line' => $diagnostic->line(),
                'category' => $case['category'],
                'message' => $legacyMessage,
                'fingerprint' => FindingFingerprint::fromDetails(
                    $diagnostic->file(),
                    $finding->category(),
                    $diagnostic->line(),
                    $legacyMessage
                )->toString(),
            ],
            $finding->toArray()
        );
    }

    /**
     * @return array<string, array{
     *     diagnostic: Diagnostic,
     *     legacy_message: string,
     *     category: string
     * }>
     */
    public static function diagnosticMappingCases(): array
    {
        return [
            DiagnosticId::AMBIGUOUS_PHPDOC_PARAMETER_TYPE => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::AMBIGUOUS_PHPDOC_PARAMETER_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->ambiguousPhpDocParameterType()',
                        'function_or_method_name' => 'ambiguousPhpDocParameterType',
                        'parameter_name' => 'value',
                        'kind' => 'method_parameter_phpdoc_ambiguous',
                        'parameter_position' => 0,
                        'phpdoc_type' => 'array',
                        'symbol' => 'voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value',
                    ]
                ),
                'legacy_message' => '[8]: missing parameter type for voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value',
                'category' => 'missing_native_type',
            ],
            DiagnosticId::AMBIGUOUS_PHPDOC_RETURN_TYPE => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::AMBIGUOUS_PHPDOC_RETURN_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                        'function_or_method_name' => 'ambiguousPhpDocReturnType',
                        'kind' => 'method_return_phpdoc_ambiguous',
                        'phpdoc_type' => 'array',
                        'symbol' => 'voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                    ]
                ),
                'legacy_message' => '[8]: missing return type for voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                'category' => 'missing_native_type',
            ],
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                    'test_file.php',
                    10,
                    ['display_name' => 'voku\tests\OldClass']
                ),
                'legacy_message' => '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
                'category' => 'deprecated_documentation',
            ],
            DiagnosticId::MISSING_NATIVE_PARAMETER_TYPE => [
                'diagnostic' => new Diagnostic(
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
                'legacy_message' => '[8]: missing parameter type for voku\tests\SimpleClass->missingParameterType() | parameter:value',
                'category' => 'missing_native_type',
            ],
            DiagnosticId::MISSING_NATIVE_PROPERTY_TYPE => [
                'diagnostic' => new Diagnostic(
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
                'legacy_message' => '[3]: missing property type for voku\tests\SimpleClass->$foo',
                'category' => 'missing_native_type',
            ],
            DiagnosticId::MISSING_NATIVE_RETURN_TYPE => [
                'diagnostic' => new Diagnostic(
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
                'legacy_message' => '[6]: missing return type for voku\tests\SimpleClass->missingReturnType()',
                'category' => 'missing_native_type',
            ],
            DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE => [
                'diagnostic' => new Diagnostic(
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
                'legacy_message' => '[8]: missing parameter type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value',
                'category' => 'missing_phpdoc_type',
            ],
            DiagnosticId::MISSING_PHPDOC_RETURN_TYPE => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::MISSING_PHPDOC_RETURN_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->missingPhpDocReturnType()',
                        'function_or_method_name' => 'missingPhpDocReturnType',
                        'kind' => 'method_return_phpdoc',
                        'missing_type' => 'null',
                        'symbol' => 'voku\tests\SimpleClass->missingPhpDocReturnType()',
                    ]
                ),
                'legacy_message' => '[8]: missing return type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocReturnType()',
                'category' => 'missing_phpdoc_type',
            ],
            DiagnosticId::PARSER_SYNTAX_ERROR => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::PARSER_SYNTAX_ERROR,
                    '',
                    null,
                    [
                        'legacy_message' => '/tmp/parser.php:370 | Syntax error, unexpected \'{\', expecting T_VARIABLE'
                            . "\n"
                            . '/tmp/parser.php on line 2',
                    ]
                ),
                'legacy_message' => '/tmp/parser.php:370 | Syntax error, unexpected \'{\', expecting T_VARIABLE'
                    . "\n"
                    . '/tmp/parser.php on line 2',
                'category' => 'parse_error',
            ],
            DiagnosticId::WRONG_PHPDOC_PARAMETER_TYPE => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::WRONG_PHPDOC_PARAMETER_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'display_name' => 'voku\tests\SimpleClass->wrongPhpDocParameterType()',
                        'function_or_method_name' => 'wrongPhpDocParameterType',
                        'parameter_name' => 'value',
                        'kind' => 'method_parameter_phpdoc_wrong',
                        'parameter_position' => 0,
                        'phpdoc_type' => 'string',
                        'native_type' => 'int',
                        'symbol' => 'voku\tests\SimpleClass->wrongPhpDocParameterType() | parameter:value',
                    ]
                ),
                'legacy_message' => '[8]: wrong parameter type "string" in phpdoc from voku\tests\SimpleClass->wrongPhpDocParameterType()  | parameter:value',
                'category' => 'wrong_phpdoc_type',
            ],
            DiagnosticId::WRONG_PHPDOC_RETURN_TYPE => [
                'diagnostic' => new Diagnostic(
                    DiagnosticId::WRONG_PHPDOC_RETURN_TYPE,
                    'test_file.php',
                    8,
                    [
                        'declaring_class' => 'voku\tests\SimpleClass',
                        'native_type' => 'int',
                        'display_name' => 'voku\tests\SimpleClass->wrongPhpDocReturnType()',
                        'function_or_method_name' => 'wrongPhpDocReturnType',
                        'kind' => 'method_return_phpdoc_wrong',
                        'phpdoc_type' => 'string',
                        'symbol' => 'voku\tests\SimpleClass->wrongPhpDocReturnType()',
                    ]
                ),
                'legacy_message' => '[8]: wrong return type "string" in phpdoc from voku\tests\SimpleClass->wrongPhpDocReturnType()',
                'category' => 'wrong_phpdoc_type',
            ],
        ];
    }
}
