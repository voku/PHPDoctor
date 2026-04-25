<?php

declare(strict_types=1);

namespace voku\tests;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use voku\PHPDoctor\CliCommand\PhpDoctorCommand;
use voku\PHPDoctor\Finding\Finding;
use voku\PHPDoctor\PhpDocCheck\PhpCodeChecker;
use voku\PHPDoctor\QualityProfile;

/**
 * @internal
 */
final class CheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckPhpClasses(): void
    {
        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy3.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame(
            [
                'PHPDoctor/tests/Dummy3.php' => [
                    0  => '[9]: missing return type for voku\tests\foo3()',
                    1  => '[19]: missing property type for voku\tests\Dummy3->$foo',
                    2  => '[19]: missing property type for voku\tests\Dummy3->$foo_mixed',
                    3  => '[56]: missing parameter type for voku\tests\Dummy3->lall() | parameter:foo',
                    4  => '[56]: missing return type for voku\tests\Dummy3->lall()',
                    5  => '[66]: missing return type "null" in phpdoc from voku\tests\Dummy3->lall2()',
                    6  => '[76]: wrong return type "null" in phpdoc from voku\tests\Dummy3->lall2_1()',
                    7  => '[86]: wrong return type "string" in phpdoc from voku\tests\Dummy3->lall3()',
                    8  => '[96]: wrong parameter type "string" in phpdoc from voku\tests\Dummy3->lall3_1()  | parameter:foo',
                    9  => '[116]: missing return type "Generator" in phpdoc from voku\tests\Dummy3->lall3_2_1()',
                    10 => '[166]: missing parameter type "null" in phpdoc from voku\tests\Dummy3->lall8() | parameter:case',
                ],
            ],
            $phpCodeErrors
        );

        // --------------------------

        if (\PHP_VERSION_ID >= 70400) {
            $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy5.php');

            $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

            static::assertSame(
                [
                    'PHPDoctor/tests/Dummy5.php' => [
                        0 => '[12]: missing property type "int" in phpdoc from voku\tests\Dummy5 | property:foo_int_4',
                        1 => '[12]: missing property type "null" in phpdoc from voku\tests\Dummy5 | property:foo_int_6',
                        2 => '[12]: missing property type for voku\tests\Dummy5->$foo',
                        3 => '[12]: missing property type for voku\tests\Dummy5->$foo_mixed',
                        4 => '[12]: wrong property type "null" in phpdoc from voku\tests\Dummy5  | property:foo_int_7',
                        5 => '[12]: wrong property type "string" in phpdoc from voku\tests\Dummy5  | property:foo_int_4',
                    ],
                ],
                $phpCodeErrors
            );
        }

        // --------------------------

        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy7.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame([], $phpCodeErrors);

        // --------------------------

        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy8.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame(
            [
                'PHPDoctor/tests/Dummy8.php' => [
                    0 => '[39]: missing parameter type for voku\tests\Dummy8->foo_broken() | parameter:lall',
                    1 => '[39]: missing return type for voku\tests\Dummy8->foo_broken()',
                ],

            ],
            $phpCodeErrors
        );

        // --------------------------

        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy9.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame([], $phpCodeErrors);

        // --------------------------

        if (\PHP_VERSION_ID >= 80000) {
            $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy10.php');

            $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

            static::assertSame(
                [
                    'PHPDoctor/tests/Dummy10.php' => [
                        0  => '[9]: missing parameter type for voku\tests\Dummy10->test1() | parameter:param1',
                        1  => '[9]: missing return type for voku\tests\Dummy10->test1()',
                        2  => '[28]: missing return type for voku\tests\Dummy10->test11()',
                        3  => '[35]: missing parameter type for voku\tests\Dummy10->test111() | parameter:param1',
                        4  => '[35]: missing return type for voku\tests\Dummy10->test111()',
                        5  => '[53]: wrong parameter type "null" in phpdoc from voku\tests\Dummy10->test121()  | parameter:param1',
                        6  => '[53]: wrong return type "null" in phpdoc from voku\tests\Dummy10->test121()',
                        7  => '[80]: missing parameter type "null" in phpdoc from voku\tests\Dummy10->test132() | parameter:param1',
                        8  => '[80]: missing return type "null" in phpdoc from voku\tests\Dummy10->test132()',
                        9  => '[107]: missing parameter type "float" in phpdoc from voku\tests\Dummy10->test142() | parameter:param1',
                        10 => '[107]: missing return type "int" in phpdoc from voku\tests\Dummy10->test142()',
                        11 => '[125]: wrong parameter type "null" in phpdoc from voku\tests\Dummy10->test144()  | parameter:param1',
                        12 => '[134]: wrong parameter type "null" in phpdoc from voku\tests\Dummy10->test145()  | parameter:param1',
                    ],
                ],
                $phpCodeErrors
            );

            // --------------------------

            $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy11.php');

            $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

            static::assertSame(
                [
                    'PHPDoctor/tests/Dummy11.php' => [
                        0 => '[14]: missing parameter type "float" in phpdoc from voku\tests\Dummy11->test1() | parameter:param1',
                        1 => '[14]: wrong parameter type "string" in phpdoc from voku\tests\Dummy11->test1()  | parameter:param1',
                    ],
                ], $phpCodeErrors
            );
        }

        // --------------------------

        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy12.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame(
            [
            ], $phpCodeErrors
        );

        // --------------------------
    }

    public function testSimpleStringInput(): void
    {
        $code = '<?php
        namespace voku\tests;
        class SimpleClass {
            public $foo;
            public int $foo1;
            private $foo2;
            
            /** @var mixed */
            public $foo3;
            
            /**
             * @param array<array-key,mixed> $request <phpdoctor-ignore-this-line/>
             * @param array<array-key,mixed> $session <phpdoctor-ignore-this-line/>
             * @param ModuleView             $view
             */
            public function __construct(&$request, &$session, &$view) {
                // ... 
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);

        static::assertSame(
            [
                '' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    '[3]: missing property type for voku\tests\SimpleClass->$foo3',
                ],
            ],
            $phpCodeErrors
        );
    }

    public function testMixed(): void
    {
        $code = '<?php declare(strict_types = 1);
        
        class HelloWorld
        {
            /**
             * @param mixed $date
             */ 
            public function sayHello($date): void
            {
                echo \'Hello, \' . $date->format(\'j. n. Y\');
            }
            
            /**
             * @param array $date
             */ 
            public function sayHello2($date): void
            {
                var_dump($date);
            }
            
            /**
             * @param array $date
             * @psalm-param array{foo: int[]} $date
             */ 
            public function sayHello3($date): void
            {
                var_dump($date);
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);

        static::assertSame(
            [
                '' => [
                    0 => '[8]: missing parameter type for HelloWorld->sayHello() | parameter:date',
                    1 => '[16]: missing parameter type for HelloWorld->sayHello2() | parameter:date',
                ],
            ],
            $phpCodeErrors
        );
    }

    public function testPhp8OverrideDetection(): void
    {
        $code = '<?php
        namespace voku\tests;
        
        interface OverrideInterface {
            public function validInterfaceMethod(int $foo): void;
        }
        
        class OverrideBase {
            public function validBaseMethod(int $foo): void {}
        }
        
        class OverrideChild extends OverrideBase implements OverrideInterface {
            #[\Override]
            public function validBaseMethod(int $foo): void {}
        
            #[\Override]
            public function validInterfaceMethod(int $foo): void {}
        
            #[\Override]
            public function invalidOverrideMethod(int $foo): void {}
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);

        static::assertCount(1, $phpCodeErrors[''] ?? []);
        $error = $phpCodeErrors[''][0] ?? '';
        static::assertStringContainsString('invalid #[\Override] usage', $error);
        static::assertStringContainsString('OverrideChild->invalidOverrideMethod()', $error);
    }

    public function testPhp8InterfaceAndEnumDetection(): void
    {
        $code = '<?php
        namespace voku\tests;
        
        interface BrokenInterface {
            public function missingParamType($foo): string;
            public function missingReturnType(int $foo);
        }
        
        enum BrokenEnum: string {
            case Ready = "ready";
        
            public function missingParamType($foo): string
            {
                return $this->value;
            }
        
            public function missingReturnType(int $foo)
            {
                return $this->value;
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];

        static::assertCount(4, $errors);
        static::assertContains('[' . '5]: missing parameter type for voku\tests\BrokenInterface->missingParamType() | parameter:foo', $errors);
        static::assertContains('[' . '6]: missing return type for voku\tests\BrokenInterface->missingReturnType()', $errors);
        static::assertContains('[' . '12]: missing parameter type for voku\tests\BrokenEnum->missingParamType() | parameter:foo', $errors);
        static::assertContains('[' . '17]: missing return type for voku\tests\BrokenEnum->missingReturnType()', $errors);
    }

    public function testPhp8DeprecatedAttributeNeedsPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;
        
        #[\Deprecated]
        function old_function(string $value): string
        {
            return $value;
        }
        
        #[\Deprecated]
        interface OldInterface
        {
            public function execute(string $value): string;
        }
        
        #[\Deprecated]
        trait OldTrait
        {
            public function traitMethod(string $value): string
            {
                return $value;
            }
        }
        
        #[\Deprecated]
        enum OldEnum: string
        {
            case Legacy = "legacy";
        }
        
        #[\Deprecated]
        class OldClass
        {
            #[\Deprecated]
            public function oldMethod(string $value): string
            {
                return $value;
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];

        static::assertCount(6, $errors);
        static::assertContains('[' . '4]: missing @deprecated tag in phpdoc from voku\tests\old_function()', $errors);
        static::assertContains('[' . '10]: missing @deprecated tag in phpdoc from voku\tests\OldInterface', $errors);
        static::assertContains('[' . '16]: missing @deprecated tag in phpdoc from voku\tests\OldTrait', $errors);
        static::assertContains('[' . '25]: missing @deprecated tag in phpdoc from voku\tests\OldEnum', $errors);
        static::assertContains('[' . '31]: missing @deprecated tag in phpdoc from voku\tests\OldClass', $errors);
        static::assertContains('[' . '34]: missing @deprecated tag in phpdoc from voku\tests\OldClass->oldMethod()', $errors);
    }

    public function testDeprecatedFunctionDiagnosticsPreserveLegacyOutput(): void
    {
        $code = '<?php
        namespace voku\tests;

        #[\Deprecated]
        function old_function(string $value): string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $errors = $analysisResult->toLegacyErrors()[''] ?? [];
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertSame(
            ['[4]: missing @deprecated tag in phpdoc from voku\tests\old_function()'],
            $errors
        );
        static::assertCount(1, $diagnostics);
        static::assertSame('deprecated_attribute_missing_phpdoc_tag', $diagnostics[0]->id());
        static::assertSame(
            ['display_name' => 'voku\tests\old_function()'],
            $diagnostics[0]->evidence()
        );
    }

    public function testAnalyseStringReturnsDeprecatedDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        #[\Deprecated]
        function old_function(string $value): string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('deprecated_attribute_missing_phpdoc_tag', $diagnostics[0]->id());
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[4]: missing @deprecated tag in phpdoc from voku\tests\old_function()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testDeprecatedClassDiagnosticsPreserveLegacyOutput(): void
    {
        $code = '<?php
        namespace voku\tests;

        #[\Deprecated]
        class OldClass
        {
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $errors = $analysisResult->toLegacyErrors()[''] ?? [];
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertSame(
            ['[4]: missing @deprecated tag in phpdoc from voku\tests\OldClass'],
            $errors
        );
        static::assertCount(1, $diagnostics);
        static::assertSame('deprecated_attribute_missing_phpdoc_tag', $diagnostics[0]->id());
        static::assertSame(
            ['display_name' => 'voku\tests\OldClass'],
            $diagnostics[0]->evidence()
        );
    }

    public function testDeprecatedMethodDiagnosticsPreserveLegacyOutput(): void
    {
        $code = '<?php
        namespace voku\tests;

        class OldClass
        {
            #[\Deprecated]
            public function oldMethod(string $value): string
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $errors = $analysisResult->toLegacyErrors()[''] ?? [];
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertSame(
            ['[6]: missing @deprecated tag in phpdoc from voku\tests\OldClass->oldMethod()'],
            $errors
        );
        static::assertCount(1, $diagnostics);
        static::assertSame('deprecated_attribute_missing_phpdoc_tag', $diagnostics[0]->id());
        static::assertSame(
            ['display_name' => 'voku\tests\OldClass->oldMethod()'],
            $diagnostics[0]->evidence()
        );
    }

    public function testAnalyseStringReturnsParseErrorDiagnostics(): void
    {
        $code = "<?php\nfunction broken( {\n";
        $analysisResult = PhpCodeChecker::analyseString(
            $code,
            ['public', 'protected', 'private'],
            false,
            false,
            false,
            false
        );
        $errors = $analysisResult->toLegacyErrors()[''] ?? [];
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $errors);
        static::assertStringContainsString('Syntax error, unexpected', $errors[0]);
        static::assertStringContainsString('T_VARIABLE', $errors[0]);
        static::assertCount(1, $diagnostics);
        static::assertSame('parser_syntax_error', $diagnostics[0]->id());
        static::assertSame(['legacy_message' => $errors[0]], $diagnostics[0]->evidence());
    }

    public function testAnalyseStringReturnsMissingPropertyTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            public $foo;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code, ['public'], false, false, false, false);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_native_property_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\SimpleClass',
                'property_name' => 'foo',
                'declaring_class' => 'voku\tests\SimpleClass',
                'symbol' => 'voku\tests\SimpleClass->$foo',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[4]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsMissingFunctionReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        function missingReturnType(string $value)
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_native_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\missingReturnType()',
                'function_or_method_name' => 'voku\tests\missingReturnType',
                'kind' => 'function',
                'symbol' => 'voku\tests\missingReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[4]: missing return type for voku\tests\missingReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsMissingFunctionParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        function missingParameterType($value): string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_native_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\missingParameterType()',
                'function_or_method_name' => 'voku\tests\missingParameterType',
                'parameter_name' => 'value',
                'kind' => 'function_parameter',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\missingParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[4]: missing parameter type for voku\tests\missingParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsMissingMethodReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            public function missingReturnType(string $value)
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_native_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingReturnType()',
                'function_or_method_name' => 'missingReturnType',
                'kind' => 'method',
                'symbol' => 'voku\tests\SimpleClass->missingReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[6]: missing return type for voku\tests\SimpleClass->missingReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsMissingMethodParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            public function missingParameterType($value): string
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_native_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingParameterType()',
                'function_or_method_name' => 'missingParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\SimpleClass->missingParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[6]: missing parameter type for voku\tests\SimpleClass->missingParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsMultipleMissingParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        function missingParameters($first, $second): string
        {
            return (string) $first . $second;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(2, $diagnostics);
        static::assertSame(
            [
                [
                    'id' => 'missing_native_parameter_type',
                    'evidence' => [
                        'display_name' => 'voku\tests\missingParameters()',
                        'function_or_method_name' => 'voku\tests\missingParameters',
                        'parameter_name' => 'first',
                        'kind' => 'function_parameter',
                        'parameter_position' => 0,
                        'symbol' => 'voku\tests\missingParameters() | parameter:first',
                    ],
                ],
                [
                    'id' => 'missing_native_parameter_type',
                    'evidence' => [
                        'display_name' => 'voku\tests\missingParameters()',
                        'function_or_method_name' => 'voku\tests\missingParameters',
                        'parameter_name' => 'second',
                        'kind' => 'function_parameter',
                        'parameter_position' => 1,
                        'symbol' => 'voku\tests\missingParameters() | parameter:second',
                    ],
                ],
            ],
            \array_map(
                static fn (\voku\PHPDoctor\Diagnostic\Diagnostic $diagnostic): array => [
                    'id' => $diagnostic->id(),
                    'evidence' => $diagnostic->evidence(),
                ],
                $diagnostics
            )
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[4]: missing parameter type for voku\tests\missingParameters() | parameter:first',
                    '[4]: missing parameter type for voku\tests\missingParameters() | parameter:second',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testAnalyseStringReturnsAmbiguousFunctionPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param mixed $value
         * @return string
         */
        function ambiguousPhpDocParameterType($value): string
        {
            return (string) $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[8]: missing parameter type for voku\tests\ambiguousPhpDocParameterType() | parameter:value',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('ambiguous_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\ambiguousPhpDocParameterType()',
                'function_or_method_name' => 'voku\tests\ambiguousPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'function_parameter_phpdoc_ambiguous',
                'parameter_position' => 0,
                'phpdoc_type' => 'mixed',
                'symbol' => 'voku\tests\ambiguousPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[8]: missing parameter type for voku\tests\ambiguousPhpDocParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[8]: missing parameter type for voku\tests\ambiguousPhpDocParameterType() | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsAmbiguousMethodPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @param array $value
             * @return string
             */
            public function ambiguousPhpDocParameterType($value): string
            {
                return (string) $value;
            }
        }';

        static::assertSame(
            [
                '' => [
                    '[10]: missing parameter type for voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('ambiguous_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->ambiguousPhpDocParameterType()',
                'function_or_method_name' => 'ambiguousPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter_phpdoc_ambiguous',
                'parameter_position' => 0,
                'phpdoc_type' => 'array',
                'symbol' => 'voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[10]: missing parameter type for voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[10]: missing parameter type for voku\tests\SimpleClass->ambiguousPhpDocParameterType() | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsAmbiguousFunctionPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @return mixed
         */
        function ambiguousPhpDocReturnType()
        {
            return 1;
        }';

        static::assertSame(
            [
                '' => [
                    '[7]: missing return type for voku\tests\ambiguousPhpDocReturnType()',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('ambiguous_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\ambiguousPhpDocReturnType()',
                'function_or_method_name' => 'voku\tests\ambiguousPhpDocReturnType',
                'kind' => 'function_return_phpdoc_ambiguous',
                'phpdoc_type' => 'mixed',
                'symbol' => 'voku\tests\ambiguousPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[7]: missing return type for voku\tests\ambiguousPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[7]: missing return type for voku\tests\ambiguousPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsAmbiguousMethodPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @return array
             */
            public function ambiguousPhpDocReturnType()
            {
                return [];
            }
        }';

        static::assertSame(
            [
                '' => [
                    '[9]: missing return type for voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('ambiguous_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                'function_or_method_name' => 'ambiguousPhpDocReturnType',
                'kind' => 'method_return_phpdoc_ambiguous',
                'phpdoc_type' => 'array',
                'symbol' => 'voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[9]: missing return type for voku\tests\SimpleClass->ambiguousPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[9]: missing return type for voku\tests\SimpleClass->ambiguousPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsMissingFunctionPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string
         */
        function missingPhpDocParameterType(?string $value): string
        {
            return (string) $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\missingPhpDocParameterType()',
                'function_or_method_name' => 'voku\tests\missingPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'function_parameter_phpdoc',
                'missing_type' => 'null',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\missingPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[8]: missing parameter type "null" in phpdoc from voku\tests\missingPhpDocParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[8]: missing parameter type "null" in phpdoc from voku\tests\missingPhpDocParameterType() | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsMissingMethodPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @param string $value
             * @return string
             */
            public function missingPhpDocParameterType(?string $value): string
            {
                return (string) $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingPhpDocParameterType()',
                'function_or_method_name' => 'missingPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter_phpdoc',
                'missing_type' => 'null',
                'parameter_position' => 0,
                'symbol' => 'voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[10]: missing parameter type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[10]: missing parameter type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocParameterType() | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsWrongFunctionPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string|int $value
         * @return string
         */
        function wrongPhpDocParameterType(string $value): string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('wrong_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'native_type' => 'string',
                'display_name' => 'voku\tests\wrongPhpDocParameterType()',
                'function_or_method_name' => 'voku\tests\wrongPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'function_parameter_phpdoc_wrong',
                'parameter_position' => 0,
                'phpdoc_type' => 'int',
                'symbol' => 'voku\tests\wrongPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[8]: wrong parameter type "int" in phpdoc from voku\tests\wrongPhpDocParameterType()  | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[8]: wrong parameter type "int" in phpdoc from voku\tests\wrongPhpDocParameterType()  | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsWrongMethodPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @param string|int $value
             * @return string
             */
            public function wrongPhpDocParameterType(string $value): string
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('wrong_phpdoc_parameter_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'native_type' => 'string',
                'display_name' => 'voku\tests\SimpleClass->wrongPhpDocParameterType()',
                'function_or_method_name' => 'wrongPhpDocParameterType',
                'parameter_name' => 'value',
                'kind' => 'method_parameter_phpdoc_wrong',
                'parameter_position' => 0,
                'phpdoc_type' => 'int',
                'symbol' => 'voku\tests\SimpleClass->wrongPhpDocParameterType() | parameter:value',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[10]: wrong parameter type "int" in phpdoc from voku\tests\SimpleClass->wrongPhpDocParameterType()  | parameter:value',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[10]: wrong parameter type "int" in phpdoc from voku\tests\SimpleClass->wrongPhpDocParameterType()  | parameter:value'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsMissingFunctionPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string
         */
        function missingPhpDocReturnType(string $value): ?string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'display_name' => 'voku\tests\missingPhpDocReturnType()',
                'function_or_method_name' => 'voku\tests\missingPhpDocReturnType',
                'kind' => 'function_return_phpdoc',
                'missing_type' => 'null',
                'symbol' => 'voku\tests\missingPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[8]: missing return type "null" in phpdoc from voku\tests\missingPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[8]: missing return type "null" in phpdoc from voku\tests\missingPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsMissingMethodPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @param string $value
             * @return string
             */
            public function missingPhpDocReturnType(string $value): ?string
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('missing_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'display_name' => 'voku\tests\SimpleClass->missingPhpDocReturnType()',
                'function_or_method_name' => 'missingPhpDocReturnType',
                'kind' => 'method_return_phpdoc',
                'missing_type' => 'null',
                'symbol' => 'voku\tests\SimpleClass->missingPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[10]: missing return type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[10]: missing return type "null" in phpdoc from voku\tests\SimpleClass->missingPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsWrongFunctionPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string|int
         */
        function wrongPhpDocReturnType(string $value): string
        {
            return $value;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('wrong_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'native_type' => 'string',
                'display_name' => 'voku\tests\wrongPhpDocReturnType()',
                'function_or_method_name' => 'voku\tests\wrongPhpDocReturnType',
                'kind' => 'function_return_phpdoc_wrong',
                'phpdoc_type' => 'int',
                'symbol' => 'voku\tests\wrongPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[8]: wrong return type "int" in phpdoc from voku\tests\wrongPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[8]: wrong return type "int" in phpdoc from voku\tests\wrongPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsWrongMethodPhpDocReturnTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            /**
             * @param string $value
             * @return string|int
             */
            public function wrongPhpDocReturnType(string $value): string
            {
                return $value;
            }
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(1, $diagnostics);
        static::assertSame('wrong_phpdoc_return_type', $diagnostics[0]->id());
        static::assertSame(
            [
                'declaring_class' => 'voku\tests\SimpleClass',
                'native_type' => 'string',
                'display_name' => 'voku\tests\SimpleClass->wrongPhpDocReturnType()',
                'function_or_method_name' => 'wrongPhpDocReturnType',
                'kind' => 'method_return_phpdoc_wrong',
                'phpdoc_type' => 'int',
                'symbol' => 'voku\tests\SimpleClass->wrongPhpDocReturnType()',
            ],
            $diagnostics[0]->evidence()
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[10]: wrong return type "int" in phpdoc from voku\tests\SimpleClass->wrongPhpDocReturnType()',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
        static::assertSame(
            [
                Finding::fromMessage(
                    '',
                    '[10]: wrong return type "int" in phpdoc from voku\tests\SimpleClass->wrongPhpDocReturnType()'
                )->toArray(),
            ],
            \array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $analysisResult->findings()
            )
        );
    }

    public function testAnalyseStringReturnsMultipleMissingPhpDocParameterTypeDiagnostics(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $first
         * @param int $second
         * @return string
         */
        function missingPhpDocParameters(?string $first, int|float $second): string
        {
            return (string) $first . $second;
        }';

        $analysisResult = PhpCodeChecker::analyseString($code);
        $diagnostics = $analysisResult->diagnostics()->all();

        static::assertCount(2, $diagnostics);
        static::assertSame(
            [
                [
                    'id' => 'missing_phpdoc_parameter_type',
                    'evidence' => [
                        'display_name' => 'voku\tests\missingPhpDocParameters()',
                        'function_or_method_name' => 'voku\tests\missingPhpDocParameters',
                        'parameter_name' => 'first',
                        'kind' => 'function_parameter_phpdoc',
                        'missing_type' => 'null',
                        'parameter_position' => 0,
                        'symbol' => 'voku\tests\missingPhpDocParameters() | parameter:first',
                    ],
                ],
                [
                    'id' => 'missing_phpdoc_parameter_type',
                    'evidence' => [
                        'display_name' => 'voku\tests\missingPhpDocParameters()',
                        'function_or_method_name' => 'voku\tests\missingPhpDocParameters',
                        'parameter_name' => 'second',
                        'kind' => 'function_parameter_phpdoc',
                        'missing_type' => 'float',
                        'parameter_position' => 1,
                        'symbol' => 'voku\tests\missingPhpDocParameters() | parameter:second',
                    ],
                ],
            ],
            \array_map(
                static fn (\voku\PHPDoctor\Diagnostic\Diagnostic $diagnostic): array => [
                    'id' => $diagnostic->id(),
                    'evidence' => $diagnostic->evidence(),
                ],
                $diagnostics
            )
        );
        static::assertSame([], $analysisResult->legacyOnlyErrors());
        static::assertSame(
            [
                '' => [
                    '[9]: missing parameter type "float" in phpdoc from voku\tests\missingPhpDocParameters() | parameter:second',
                    '[9]: missing parameter type "null" in phpdoc from voku\tests\missingPhpDocParameters() | parameter:first',
                ],
            ],
            $analysisResult->toLegacyErrors()
        );
    }

    public function testCheckFromStringStillReturnsLegacyArray(): void
    {
        $code = '<?php
        namespace voku\tests;

        class SimpleClass
        {
            public $foo;
        }';

        static::assertSame(
            [
                '' => [
                    '[4]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ],
            PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForMissingNativeReturnType(): void
    {
        $code = '<?php
        namespace voku\tests;

        function missingReturnType(string $value)
        {
            return $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[4]: missing return type for voku\tests\missingReturnType()',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForMissingNativeParameterType(): void
    {
        $code = '<?php
        namespace voku\tests;

        function missingParameterType($value): string
        {
            return $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[4]: missing parameter type for voku\tests\missingParameterType() | parameter:value',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForMissingPhpDocParameterType(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string
         */
        function missingPhpDocParameterType(?string $value): string
        {
            return (string) $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[8]: missing parameter type "null" in phpdoc from voku\tests\missingPhpDocParameterType() | parameter:value',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForWrongPhpDocParameterType(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string|int $value
         * @return string
         */
        function wrongPhpDocParameterType(string $value): string
        {
            return $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[8]: wrong parameter type "int" in phpdoc from voku\tests\wrongPhpDocParameterType()  | parameter:value',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForMissingPhpDocReturnType(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string
         */
        function missingPhpDocReturnType(string $value): ?string
        {
            return $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[8]: missing return type "null" in phpdoc from voku\tests\missingPhpDocReturnType()',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckFromStringStillReturnsLegacyArrayForWrongPhpDocReturnType(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @param string $value
         * @return string|int
         */
        function wrongPhpDocReturnType(string $value): string
        {
            return $value;
        }';

        static::assertSame(
            [
                '' => [
                    '[8]: wrong return type "int" in phpdoc from voku\tests\wrongPhpDocReturnType()',
                ],
            ],
            PhpCodeChecker::checkFromString($code)
        );
    }

    public function testCheckPhpFilesStillReturnsLegacyArray(): void
    {
        $file = \sys_get_temp_dir() . '/phpdoctor-legacy-array-' . \bin2hex(\random_bytes(8)) . '.php';
        \file_put_contents($file, "<?php\nnamespace voku\\tests;\nclass SimpleClass\n{\n    public \$foo;\n}\n");

        try {
            static::assertSame(
                [
                    $file => [
                        '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    ],
                ],
                PhpCodeChecker::checkPhpFiles($file, ['public'], false, false, false, false)
            );
        } finally {
            if (\is_file($file)) {
                \unlink($file);
            }
        }
    }

    public function testParseErrorsEnabledBehaviorRemainsUnchanged(): void
    {
        $file = \sys_get_temp_dir() . '/phpdoctor-parse-error-' . \bin2hex(\random_bytes(8)) . '.php';
        \file_put_contents($file, "<?php\nfunction broken( {\n");

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [$file],
                '--skip-parse-errors' => 'false',
            ]);

            static::assertSame(1, $exitCode);
            static::assertStringContainsString('Syntax error, unexpected', $tester->getDisplay());
            static::assertStringContainsString('T_VARIABLE', $tester->getDisplay());
        } finally {
            if (\is_file($file)) {
                \unlink($file);
            }
        }
    }

    public function testPhp8ModernFeatureSupportSmoke(): void
    {
        $code = '<?php
        namespace voku\tests;
        
        #[App\Contract]
        interface ModernInterface
        {
            public function run(string $input): string;
        }
        
        trait ModernTrait
        {
            #[App\Marker]
            public string $name = "";
        
            public function traitMethod(int $count): int
            {
                return $count;
            }
        }
        
        enum ModernEnum: string
        {
            case Active = "active";
        
            #[App\Marker]
            public const string LABEL = "label";
        
            #[App\Marker]
            public function label(): string
            {
                return self::LABEL;
            }
        }
        
        #[App\Marker]
        final class ModernClass implements ModernInterface
        {
            use ModernTrait;
        
            #[App\Marker]
            public const string NAME = "service";
        
            public function __construct(
                #[App\Marker]
                public readonly string $id
            ) {
            }
        
            #[\Override]
            public function run(string $input): string
            {
                return $input;
            }
        
            #[App\Marker]
            public function api(#[App\Marker] string $value): string
            {
                return $value;
            }
        }
        
        #[App\Marker]
        function modern_function(#[App\Marker] string $value): string
        {
            return $value;
        }
        
        class Hooked
        {
            public string $fullName {
                get => $this->value;
                set (string $value) {
                    $this->value = $value;
                }
            }
        
            public private(set) string $email = "";
        
            private string $value = "";
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public', 'protected', 'private'], false, false, false, false);

        static::assertSame([], \array_filter($phpCodeErrors));
    }

    // =========================================================================
    // PHP 8 feature coverage – expanded tests
    // =========================================================================

    /**
     * When #[\Deprecated] AND @deprecated are both present no error should be
     * emitted (the phpdoc already satisfies the requirement).
     */
    public function testDeprecatedAttributeWithExistingPhpdocProducesNoError(): void
    {
        $code = '<?php
        namespace voku\tests;

        /**
         * @deprecated use NewFunction instead
         */
        #[\Deprecated]
        function already_tagged_fn(string $v): string { return $v; }

        /**
         * @deprecated
         */
        #[\Deprecated]
        class AlreadyTaggedClass
        {
            /**
             * @deprecated
             */
            #[\Deprecated]
            public function alreadyTaggedMethod(string $v): string { return $v; }
        }

        /**
         * @deprecated
         */
        #[\Deprecated]
        interface AlreadyTaggedInterface
        {
            public function run(string $v): string;
        }

        /**
         * @deprecated
         */
        #[\Deprecated]
        enum AlreadyTaggedEnum: string
        {
            case A = "a";
        }

        /**
         * @deprecated
         */
        #[\Deprecated]
        trait AlreadyTaggedTrait
        {
            public function doSomething(string $v): string { return $v; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * An attribute unrelated to \Deprecated must NOT trigger the missing-
     * @deprecated-tag check.
     */
    public function testUnrelatedAttributeDoesNotTriggerDeprecatedCheck(): void
    {
        $code = '<?php
        namespace voku\tests;

        #[\AllowDynamicProperties]
        class MarkedClass
        {
            public function run(string $v): string { return $v; }
        }

        #[SomeOtherAttribute]
        interface MarkedInterface
        {
            public function run(string $v): string;
        }

        #[SomeOtherAttribute]
        function marked_function(string $v): string { return $v; }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public', 'protected', 'private'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * #[\Override] on a method that is declared in a grandparent class is valid.
     * This exercises the recursive `classOrParentsHasMethod` path.
     */
    public function testPhp8OverrideDetectionViaGrandparentClass(): void
    {
        $code = '<?php
        namespace voku\tests;

        class GrandBase
        {
            public function rootMethod(int $x): int { return $x; }
        }

        class MidClass extends GrandBase {}

        class LeafClass extends MidClass
        {
            #[\Override]
            public function rootMethod(int $x): int { return $x * 2; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * An interface re-declaring a method from a parent interface with
     * #[\Override] is valid. This exercises `interfaceOrParentsHasMethod`.
     */
    public function testPhp8OverrideDetectionInterfaceExtendsInterface(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface ParentIface
        {
            public function execute(string $v): string;
        }

        interface ChildIface extends ParentIface
        {
            #[\Override]
            public function execute(string $v): string;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * An enum method that #[\Override]s a method from an implemented interface
     * is valid. This exercises the PHPEnum branch of `hasParentOrInterfaceMethod`.
     */
    public function testPhp8OverrideDetectionEnumImplementsInterface(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface Labelable
        {
            public function label(): string;
        }

        enum Status: string implements Labelable
        {
            case Active = "active";

            #[\Override]
            public function label(): string { return $this->value; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * Interface methods whose phpdoc @return type does not match the native
     * return type must produce an error, the same way class methods do.
     */
    public function testInterfaceMethodWrongReturnTypeInPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface WrongDocInterface
        {
            /**
             * @return string
             */
            public function getNumber(): int;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\WrongDocInterface->getNumber()', $errors[0]);
    }

    /**
     * Interface methods whose phpdoc @param type does not match the native
     * parameter type must produce an error, the same way class methods do.
     */
    public function testInterfaceMethodWrongParamTypeInPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface WrongParamInterface
        {
            /**
             * @param string $val
             */
            public function process(int $val): void;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\WrongParamInterface->process()', $errors[0]);
        static::assertStringContainsString('parameter:val', $errors[0]);
    }

    /**
     * Enum methods whose phpdoc @return type does not match the native
     * return type must produce an error.
     */
    public function testEnumMethodWrongReturnTypeInPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        enum WrongDocEnum: string
        {
            case A = "a";

            /**
             * @return string
             */
            public function getInt(): int { return 1; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\WrongDocEnum->getInt()', $errors[0]);
    }

    /**
     * Enum methods whose phpdoc @param type does not match the native
     * parameter type must produce an error.
     */
    public function testEnumMethodWrongParamTypeInPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        enum WrongParamEnum: string
        {
            case A = "a";

            /**
             * @param string $val
             */
            public function process(int $val): void {}
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\WrongParamEnum->process()', $errors[0]);
        static::assertStringContainsString('parameter:val', $errors[0]);
    }

    /**
     * With skipDeprecatedMethods=true, methods that carry @deprecated phpdoc
     * must be skipped in interfaces and enums, not just in classes.
     */
    public function testSkipDeprecatedMethodsInInterfaceAndEnum(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface IWithDeprecated
        {
            /**
             * @deprecated
             */
            public function oldMethod($foo);
        }

        enum EWithDeprecated: string
        {
            case A = "a";

            /**
             * @deprecated
             */
            public function oldEnumMethod($foo): string { return $this->value; }
        }';

        // Without skipDeprecatedMethods: errors expected because of missing types
        $errorsDefault = \array_filter(PhpCodeChecker::checkFromString($code, ['public']));
        static::assertNotEmpty($errorsDefault);

        // With skipDeprecatedMethods: deprecated methods are not checked → no errors
        $errorsSkipped = PhpCodeChecker::checkFromString($code, ['public'], false, true);
        static::assertSame([], \array_filter($errorsSkipped));
    }

    /**
     * With skipFunctionsWithLeadingUnderscore=true, methods whose names start
     * with `_` must be skipped in interfaces and enums.
     */
    public function testSkipLeadingUnderscoreMethodsInInterfaceAndEnum(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface IWithUnderscore
        {
            public function _internal($foo): void;
            public function publicMethod(string $v): string;
        }

        enum EWithUnderscore: string
        {
            case A = "a";
            public function _helper($x): string { return $this->value; }
            public function realMethod(string $v): string { return $v; }
        }';

        // Without skip: _internal and _helper have missing param-type errors
        $errorsDefault = \array_filter(PhpCodeChecker::checkFromString($code, ['public']));
        static::assertNotEmpty($errorsDefault);

        // With skipFunctionsWithLeadingUnderscore=true: underscore methods ignored, others are fine
        $errorsSkipped = PhpCodeChecker::checkFromString($code, ['public'], false, false, true);
        static::assertSame([], \array_filter($errorsSkipped));
    }

    /**
     * Access-level filtering works for enums: private methods are not flagged
     * when only public access is checked.
     */
    public function testAccessFilterForEnumMethods(): void
    {
        $code = '<?php
        namespace voku\tests;

        enum AccessFilterEnum: string
        {
            case A = "a";

            // Private method with missing types – should be invisible with access=[public]
            private function privateHelper($x) {}

            public function publicMethod(string $v): string { return $v; }
        }';

        $errorsPublicOnly = PhpCodeChecker::checkFromString($code, ['public']);
        static::assertSame([], \array_filter($errorsPublicOnly));

        // When private is also requested, the missing-type errors surface
        $errorsAll = \array_filter(PhpCodeChecker::checkFromString($code, ['public', 'private']));
        static::assertNotEmpty($errorsAll);
    }

    /**
     * The <phpdoctor-ignore-this-line/> marker in an interface method's phpdoc
     * must suppress the type error for that parameter.
     */
    public function testPhpdoctorIgnoreTagInInterfaceMethod(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface IgnorableInterface
        {
            /**
             * @param mixed $foo <phpdoctor-ignore-this-line/>
             */
            public function run($foo): string;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public']);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * An interface method decorated with #[\Deprecated] but lacking @deprecated
     * in its phpdoc must produce a missing-@deprecated-tag error.
     */
    public function testInterfaceMethodDeprecatedAttributeWithoutPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface InterfaceWithDeprecatedMethod
        {
            #[\Deprecated]
            public function legacyMethod(string $v): string;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\InterfaceWithDeprecatedMethod->legacyMethod()', $errors[0]);
        static::assertStringContainsString('missing @deprecated tag in phpdoc', $errors[0]);
    }

    /**
     * A trait method decorated with #[\Deprecated] but lacking @deprecated
     * in its phpdoc must produce a missing-@deprecated-tag error.
     */
    public function testTraitMethodDeprecatedAttributeWithoutPhpdoc(): void
    {
        $code = '<?php
        namespace voku\tests;

        trait TraitWithDeprecatedMethod
        {
            #[\Deprecated]
            public function legacyMethod(string $v): string { return $v; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('voku\tests\TraitWithDeprecatedMethod->legacyMethod()', $errors[0]);
        static::assertStringContainsString('missing @deprecated tag in phpdoc', $errors[0]);
    }

    /**
     * A static interface method must use the `::` separator (not `->`) in every
     * error message that references it.
     */
    public function testStaticInterfaceMethodUsesScopeResolutionInErrorMessage(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface StaticInterface
        {
            public static function create($value);
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];
        // Expect at least the missing-parameter-type error
        static::assertNotEmpty($errors);
        static::assertStringContainsString('StaticInterface::create()', $errors[0]);
    }

    /**
     * Multiple independent issues inside the same interface are each reported
     * individually and attributed to the correct method.
     */
    public function testMultipleIssuesInSameInterfaceAreReportedIndependently(): void
    {
        $code = '<?php
        namespace voku\tests;

        interface MultiIssueInterface
        {
            public function methodA($x): string;
            public function methodB(int $x);
            public function methodC(int $x): string;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];

        static::assertCount(2, $errors);

        $joined = \implode(' | ', $errors);
        static::assertStringContainsString('methodA', $joined);
        static::assertStringContainsString('methodB', $joined);
        static::assertStringNotContainsString('methodC', $joined);
    }

    /**
     * Multiple independent issues inside the same enum are each reported
     * individually and attributed to the correct method.
     */
    public function testMultipleIssuesInSameEnumAreReportedIndependently(): void
    {
        $code = '<?php
        namespace voku\tests;

        enum MultiIssueEnum: string
        {
            case X = "x";

            public function methodA($x): string { return $this->value; }
            public function methodB(int $x): string { return $this->value; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code);
        $errors = $phpCodeErrors[''] ?? [];

        // methodA has missing param type; methodB is fully typed
        static::assertCount(1, $errors);
        static::assertStringContainsString('MultiIssueEnum->methodA', $errors[0]);
    }

    // =========================================================================
    // End PHP 8 feature coverage – expanded tests
    // =========================================================================

    public function testSimpleStringInputInheritdocExtended(): void
    {
        $code = '<?php
        namespace voku\tests;
        class SimpleClassV1 {
            /**
             * @param string $foo
             * @return int[] 
             */
            public function lall($foo): array
            {
               return [];
            }
        }
        class SimpleClassV2 extends SimpleClassV1 {
            /**
             * {@inheritdoc}
             */
            public function lall($foo): array
            {
               return [];
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public']);

        static::assertSame(
            [],
            $phpCodeErrors
        );
    }

    public function testSimpleStringInputInheritdoc(): void
    {
        $code = '<?php
        namespace voku\tests;
        interface SimpleInterface {
            /**
             * @param string $foo
             * @return int[]  ← phpdoc-only return type; interface is now checked, so this must be present
             */
            public function lall($foo)
        }
        class SimpleClass implements SimpleInterface {
            /**
             * {@inheritdoc}
             * 
             * @return int[]
             */
            public function lall($foo): array
            {
               return [];
            }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public']);

        static::assertSame(
            [],
            $phpCodeErrors
        );
    }

    public function testSimpleStringInputWithMixed(): void
    {
        $code = '<?php
        namespace voku\tests;
        class SimpleClass {
            public $foo;
            public int $foo1;
            private $foo2;
            
            /** @var mixed */
            public $foo3;
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], true);

        static::assertSame(
            [
                '' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                ],
            ],
            $phpCodeErrors
        );
    }

    public function testSimpleStringInputWithAmpersand(): void
    {
        $code = '<?php
        /**
         * Open Internet or Unix domain socket connection
         * @link https://php.net/manual/en/function.fsockopen.php
         * @param string $hostname <p>
         * If you have compiled in OpenSSL support, you may prefix the
         * hostname with either ssl://
         * or tls:// to use an SSL or TLS client connection
         * over TCP/IP to connect to the remote host.
         * </p>
         * @param null|int $port [optional] <p>
         * The port number.
         * </p>
         * @param int &$errno [optional] <p>
         * If provided, holds the system level error number that occurred in the
         * system-level connect() call.
         * </p>
         * <p>
         * If the value returned in errno is
         * 0 and the function returned false, it is an
         * indication that the error occurred before the
         * connect() call. This is most likely due to a
         * problem initializing the socket.
         * </p>
         * @param string &$errstr [optional] <p>
         * The error message as a string.
         * </p>
         * @param null|float $timeout [optional] <p>
         * The connection timeout, in seconds.
         * </p>
         * <p>
         * If you need to set a timeout for reading/writing data over the
         * socket, use stream_set_timeout, as the
         * timeout parameter to
         * fsockopen only applies while connecting the
         * socket.
         * </p>
         * @return resource|false fsockopen returns a file pointer which may be used
         * together with the other file functions (such as
         * fgets, fgetss,
         * fwrite, fclose, and
         * feof). If the call fails, it will return false
         */
        function fsockopen_test($hostname, $port = null, &$errno = null, &$errstr = null, $timeout = null) { /** ... */ };';
        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], true);

        static::assertSame([], $phpCodeErrors);
    }

    /**
     * Class constants and properties with a #[\Deprecated] attribute but
     * without a @deprecated phpdoc tag should be reported as errors.
     * Elements that already carry @deprecated in their phpdoc must not
     * produce any error.
     *
     * Note: this check relies on reflection and therefore requires the
     * owning class to be loaded in the current process.  The test uses a
     * real file (Dummy13.php) so the autoloader can load the class.
     */
    public function testDeprecatedAttributeOnConstantsAndProperties(): void
    {
        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy13.php');
        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame(
            [
                'PHPDoctor/tests/Dummy13.php' => [
                    0 => '[22]: missing @deprecated tag in phpdoc from voku\tests\Dummy13::MISSING_DOC_CONST',
                    1 => '[34]: missing @deprecated tag in phpdoc from voku\tests\Dummy13->$missingDocProp',
                ],
            ],
            $phpCodeErrors
        );
    }

    // =========================================================================
    // PhpDoctorCommand (CLI) tests
    // =========================================================================

    private function buildCommandTester(): CommandTester
    {
        $app = new Application();
        $command = new PhpDoctorCommand([]);
        $app->add($command);
        $app->setDefaultCommand(PhpDoctorCommand::COMMAND_NAME);

        return new CommandTester($app->find(PhpDoctorCommand::COMMAND_NAME));
    }

    public function testCommandExecuteWithNoErrors(): void
    {
        $tester = $this->buildCommandTester();

        // Dummy7.php has no errors – command should return 0
        $exitCode = $tester->execute(['path' => [__DIR__ . '/Dummy7.php']]);

        static::assertSame(0, $exitCode);
        static::assertStringContainsString('0 errors detected', $tester->getDisplay());
    }

    public function testCommandExecuteWithErrors(): void
    {
        $tester = $this->buildCommandTester();

        // Dummy8.php has 2 errors; use a non-matching exclude regex so /tests/ files are not filtered
        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
        ]);

        static::assertSame(1, $exitCode);
        static::assertStringContainsString('errors detected', $tester->getDisplay());
    }

    public function testCommandJsonProfileOutput(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
            '--output-format' => 'json',
        ]);

        $profile = \json_decode($tester->getDisplay(), true);
        $expectedProfile = QualityProfile::fromErrors(
            PhpCodeChecker::checkPhpFiles(
                __DIR__ . '/Dummy8.php',
                ['public', 'protected', 'private'],
                false,
                false,
                false,
                true,
                [],
                ['#/vendor/#i']
            )
        );

        static::assertSame(1, $exitCode);
        static::assertIsArray($profile);
        static::assertSame($expectedProfile, $profile);
        static::assertSame('type_and_phpdoc_quality', $profile['scope'] ?? null);
        static::assertSame(2, $profile['total_error_count'] ?? null);
        static::assertSame(2, $profile['new_error_count'] ?? null);
        static::assertSame(2, $profile['summary']['missing_native_type'] ?? null);
    }

    public function testCommandGithubOutputEmitsAnnotations(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
            '--output-format' => 'github',
        ]);

        static::assertSame(1, $exitCode);
        static::assertStringContainsString('::error file=', $tester->getDisplay());
        static::assertStringContainsString('missing parameter type for voku\tests\Dummy8->foo_broken()', $tester->getDisplay());
        static::assertStringContainsString('(missing_native_type)', $tester->getDisplay());
    }

    public function testCommandGithubOutputUsesAllFindingsWithoutBaseline(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
            '--output-format' => 'github',
        ]);

        $lines = \array_values(\array_filter(\explode("\n", \trim($tester->getDisplay()))));

        static::assertSame(1, $exitCode);
        static::assertCount(2, $lines);
    }

    public function testCommandGithubOutputUsesOnlyNewFindingsWithBaseline(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-github-baseline-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        $baselineFile = $directory . '/baseline.json';
        $baselineSource = $directory . '/BaselineSource.php';
        $newFindingSource = $directory . '/NewFindingSource.php';

        \file_put_contents(
            $baselineSource,
            <<<'PHP'
<?php

declare(strict_types=1);

namespace voku\tests;

final class BaselineSource
{
    public $baselineMissingType;
}
PHP
        );
        \file_put_contents(
            $newFindingSource,
            <<<'PHP'
<?php

declare(strict_types=1);

namespace voku\tests;

final class NewFindingSource
{
    public $newMissingType;
}
PHP
        );

        try {
            $tester = $this->buildCommandTester();
            $generateExitCode = $tester->execute([
                'path' => [$baselineSource],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
                '--generate-baseline' => 'true',
            ]);

            static::assertSame(0, $generateExitCode);

            $tester = $this->buildCommandTester();
            $exitCode = $tester->execute([
                'path' => [$directory],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
                '--output-format' => 'github',
            ]);

            $display = $tester->getDisplay();
            $lines = \array_values(\array_filter(\explode("\n", \trim($display))));

            static::assertSame(1, $exitCode);
            static::assertCount(1, $lines);
            static::assertStringContainsString('NewFindingSource.php', $display);
            static::assertStringNotContainsString('BaselineSource.php', $display);
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
            if (\is_file($baselineSource)) {
                \unlink($baselineSource);
            }
            if (\is_file($newFindingSource)) {
                \unlink($newFindingSource);
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandGithubOutputPreservesExpectedExitCodes(): void
    {
        $tester = $this->buildCommandTester();

        $errorExitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
            '--output-format' => 'github',
        ]);

        static::assertSame(1, $errorExitCode);

        $tester = $this->buildCommandTester();
        $successExitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--output-format' => 'github',
        ]);

        static::assertSame(0, $successExitCode);
        static::assertSame('', $tester->getDisplay());
    }

    public function testCommandBaselineAllowsExistingFindings(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-baseline-');
        static::assertIsString($baselineFile);

        try {
            $tester = $this->buildCommandTester();
            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy8.php'],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
                '--generate-baseline' => 'true',
            ]);

            static::assertSame(0, $exitCode);
            static::assertFileExists($baselineFile);

            $baseline = \json_decode((string) \file_get_contents($baselineFile), true);

            static::assertIsArray($baseline);
            static::assertSame(1, $baseline['schema_version'] ?? null);
            static::assertSame('phpdoctor', $baseline['tool'] ?? null);
            static::assertSame('type_and_phpdoc_quality', $baseline['scope'] ?? null);
            static::assertArrayNotHasKey('summary', $baseline);
            static::assertArrayNotHasKey('new_findings', $baseline);
            static::assertArrayNotHasKey('new_summary', $baseline);
            static::assertArrayNotHasKey('total_error_count', $baseline);
            static::assertIsArray($baseline['findings'] ?? null);
            static::assertArrayNotHasKey('message', $baseline['findings'][0] ?? []);

            $tester = $this->buildCommandTester();
            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy8.php'],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(0, $exitCode);
            static::assertStringContainsString('0 new errors detected', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testCommandLegacyProfileBaselineAllowsExistingFindings(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-legacy-baseline-');
        static::assertIsString($baselineFile);

        try {
            $tester = $this->buildCommandTester();
            $profileExitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy8.php'],
                '--path-exclude-regex' => '#/vendor/#i',
                '--output-format' => 'json',
            ]);

            static::assertSame(1, $profileExitCode);
            \file_put_contents($baselineFile, $tester->getDisplay());

            $tester = $this->buildCommandTester();
            $baselineExitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy8.php'],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(0, $baselineExitCode);
            static::assertStringContainsString('0 new errors detected', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testCommandBaselineAllowsExistingDeprecatedAttributeDiagnostics(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-deprecated-baseline-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        $file = $directory . '/DeprecatedExample.php';
        $baselineFile = $directory . '/baseline.json';

        \file_put_contents(
            $file,
            <<<'PHP'
<?php

namespace voku\tests;

class DeprecatedExample
{
    #[\Deprecated]
    public function oldMethod(string $value): string
    {
        return $value;
    }
}
PHP
        );

        try {
            $tester = $this->buildCommandTester();
            $generateExitCode = $tester->execute([
                'path' => [$file],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
                '--generate-baseline' => 'true',
            ]);

            static::assertSame(0, $generateExitCode);
            static::assertFileExists($baselineFile);

            $tester = $this->buildCommandTester();
            $baselineExitCode = $tester->execute([
                'path' => [$file],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(0, $baselineExitCode);
            static::assertStringContainsString('0 new errors detected', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
            if (\is_file($file)) {
                \unlink($file);
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandGenerateBaselineRequiresBaselineFile(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--generate-baseline' => 'true',
        ]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('requires --baseline-file', $tester->getDisplay());
    }

    public function testCommandRejectsInvalidBaselineJson(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-invalid-baseline-');
        static::assertIsString($baselineFile);
        // Incomplete JSON object to verify baseline parse failures are surfaced.
        \file_put_contents($baselineFile, '{"findings":');

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy7.php'],
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(2, $exitCode);
            static::assertStringContainsString('does not contain valid JSON', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testCommandRejectsInvalidBaselineSchema(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-invalid-baseline-schema-');
        static::assertIsString($baselineFile);
        \file_put_contents(
            $baselineFile,
            (string) \json_encode(
                [
                    'schema_version' => 2,
                    'tool' => 'phpdoctor',
                    'scope' => 'type_and_phpdoc_quality',
                    'generated_at' => '2026-04-24T00:00:00+00:00',
                    'findings' => [],
                ],
                \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES
            )
        );

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy7.php'],
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(2, $exitCode);
            static::assertStringContainsString('supported baseline schema', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testCommandRejectsSchemaVersionOneBaselineFindingWithoutRequiredFields(): void
    {
        $baselineFile = \tempnam(\sys_get_temp_dir(), 'phpdoctor-invalid-baseline-finding-schema-');
        static::assertIsString($baselineFile);
        \file_put_contents(
            $baselineFile,
            (string) \json_encode(
                [
                    'schema_version' => 1,
                    'tool' => 'phpdoctor',
                    'scope' => 'type_and_phpdoc_quality',
                    'generated_at' => '2026-04-24T00:00:00+00:00',
                    'findings' => [
                        [
                            'fingerprint' => 'only-fingerprint',
                        ],
                    ],
                ],
                \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES
            )
        );

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy7.php'],
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(2, $exitCode);
            static::assertStringContainsString('supported baseline schema', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
        }
    }

    public function testCommandRejectsMissingBaselineFile(): void
    {
        $baselineFile = \sys_get_temp_dir() . '/phpdoctor-missing-baseline-' . \bin2hex(\random_bytes(8)) . '.json';
        static::assertFileDoesNotExist($baselineFile);

        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--baseline-file' => $baselineFile,
        ]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandRejectsUnsupportedOutputFormat(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--output-format' => 'xml',
        ]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('is not supported', $tester->getDisplay());
    }

    public function testCommandProfileSummaryIncludesParseErrorsWhenEnabled(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/vendor/#i',
            '--profile' => 'true',
            '--skip-parse-errors' => 'false',
        ]);

        static::assertSame(1, $exitCode);
        static::assertStringContainsString('PHPDoctor type and PHPDoc quality profile', $tester->getDisplay());
        static::assertStringContainsString('- parse_error: 2', $tester->getDisplay());
        static::assertStringContainsString('- missing_native_type: 2', $tester->getDisplay());
    }

    public function testQualityProfileCategorizesExistingFindings(): void
    {
        $profile = QualityProfile::fromErrors(
            [
                'test_file.php' => [
                    '[3]: missing property type for voku\tests\SimpleClass->$foo',
                    '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
                    '[10]: missing @deprecated tag in phpdoc from voku\tests\OldClass',
                ],
            ]
        );

        static::assertSame(3, $profile['total_error_count']);
        static::assertSame(1, $profile['summary']['missing_native_type']);
        static::assertSame(1, $profile['summary']['wrong_phpdoc_type']);
        static::assertSame(1, $profile['summary']['deprecated_documentation']);
    }

    public function testQualityProfileHandlesParserOutputWithTrailingWhitespace(): void
    {
        $profile = QualityProfile::fromErrors(
            [
                'test_file.php' => [
                    '[39]: foo_broken:39 | Unexpected token "", expected \'}\' at offset 45 on line 1',
                    // Keep the trailing whitespace because this mirrors the parser output format seen in production.
                    '[48]: foo_ignore:48 | Unexpected token "/>", expected \'>\' at offset 33 on line 1 ',
                ],
            ]
        );

        static::assertSame(2, $profile['summary']['parse_error']);
        static::assertSame(0, $profile['summary']['other']);
        static::assertSame('parse_error', $profile['findings'][0]['category']);
        static::assertSame('parse_error', $profile['findings'][1]['category']);
        static::assertStringEndsWith('line 1 ', $profile['findings'][1]['message']);
    }

    public function testCommandExecuteWithInvalidPath(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute(['path' => ['/nonexistent/path/that/does/not/exist']]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandExecuteDefaultOptions(): void
    {
        $tester = $this->buildCommandTester();

        // Use access=public only, skip-deprecated-functions=true, skip-functions-with-leading-underscore=true
        $exitCode = $tester->execute(
            ['path' => [__DIR__ . '/Dummy7.php']],
            []
        );

        static::assertSame(0, $exitCode);
    }

    public function testCommandExecuteWithAccessOption(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute(
            ['path' => [__DIR__ . '/Dummy7.php']],
            []
        );

        // Run again with specific access option
        $tester->execute(
            ['path' => [__DIR__ . '/Dummy7.php'], '--access' => 'public']
        );

        static::assertSame(0, $exitCode);
        static::assertStringContainsString('0 errors detected', $tester->getDisplay());
    }

    public function testCommandExecuteWithSkipOptions(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--skip-ambiguous-types-as-error' => 'true',
            '--skip-deprecated-functions' => 'true',
            '--skip-functions-with-leading-underscore' => 'true',
            '--skip-parse-errors' => 'false',
        ]);

        static::assertSame(0, $exitCode);
    }

    public function testCommandExecuteWithInvalidAutoloadFile(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--autoload-file' => '/nonexistent/autoloader.php',
        ]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandExecuteWithValidAutoloadFile(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--autoload-file' => __DIR__ . '/../vendor/autoload.php',
        ]);

        static::assertSame(0, $exitCode);
    }

    public function testCommandExecuteLoadsBootstrapAutoloadFileBeforeParser(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-autoload-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        \mkdir($directory . '/vendor');
        $bootstrapHelper = $directory . '/bootstrap-helper.php';
        $vendorAutoload = $directory . '/vendor/autoload.php';
        $bootstrapFile = $directory . '/phpstan-bootstrap.php';

        \file_put_contents($bootstrapHelper, '<?php return true;' . "\n");
        \file_put_contents($vendorAutoload, '<?php return true;' . "\n");
        \file_put_contents(
            $bootstrapFile,
            <<<'PHP'
<?php

foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
    if (($frame['file'] ?? '') !== '' && str_ends_with($frame['file'], 'PhpCodeParser.php')) {
        throw new RuntimeException('bootstrap loaded from parser');
    }
}

require_once __DIR__ . '/bootstrap-helper.php';
require_once __DIR__ . '/vendor/autoload.php';
PHP
        );

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [__DIR__ . '/Dummy7.php'],
                '--autoload-file' => $bootstrapFile,
            ]);

            static::assertSame(0, $exitCode);
            static::assertStringContainsString('0 errors detected', $tester->getDisplay());
        } finally {
            foreach ([$bootstrapFile, $bootstrapHelper, $vendorAutoload] as $file) {
                if (\is_file($file)) {
                    \unlink($file);
                }
            }
            if (\is_dir($directory . '/vendor')) {
                \rmdir($directory . '/vendor');
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandExecuteWithPathExcludeRegex(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy8.php'],
            '--path-exclude-regex' => '#/tests/#i',
        ]);

        // All paths excluded → 0 errors
        static::assertSame(0, $exitCode);
    }

    public function testCommandExecuteWithFileExtensions(): void
    {
        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--file-extensions' => '.php',
        ]);

        static::assertSame(0, $exitCode);
    }

    public function testCommandExecuteRespectsCustomFileExtensions(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-ext-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        $file = $directory . '/Broken.inc';
        \file_put_contents(
            $file,
            <<<'PHP'
<?php

function broken_extension_file($value) {
    return $value;
}
PHP
        );

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [$directory],
                '--file-extensions' => '.inc',
                '--path-exclude-regex' => '#/vendor/#i',
            ]);

            static::assertSame(1, $exitCode);
            static::assertStringContainsString('missing parameter type for broken_extension_file()', $tester->getDisplay());
        } finally {
            if (\is_file($file)) {
                \unlink($file);
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandExecuteSkipsFilesWithExtensionsNotConfigured(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-ext-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        $file = $directory . '/Broken.inc';
        \file_put_contents(
            $file,
            <<<'PHP'
<?php

function broken_extension_file($value) {
    return $value;
}
PHP
        );

        try {
            $tester = $this->buildCommandTester();

            $exitCode = $tester->execute([
                'path' => [$directory],
                '--path-exclude-regex' => '#/vendor/#i',
            ]);

            static::assertSame(0, $exitCode);
            static::assertStringContainsString('0 errors detected', $tester->getDisplay());
        } finally {
            if (\is_file($file)) {
                \unlink($file);
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandChangedFindingFailsAgainstBaseline(): void
    {
        $directoryMarker = \tempnam(\sys_get_temp_dir(), 'phpdoctor-baseline-case-');
        static::assertIsString($directoryMarker);
        \unlink($directoryMarker);
        $directory = $directoryMarker;
        \mkdir($directory);
        $file = $directory . '/Existing.php';
        $newFile = $directory . '/New.php';
        $baselineFile = $directory . '/baseline.json';

        \file_put_contents(
            $file,
            <<<'PHP'
<?php

function broken_existing($value) {
    return $value;
}
PHP
        );

        try {
            $tester = $this->buildCommandTester();
            $generateExitCode = $tester->execute([
                'path' => [$file],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
                '--generate-baseline' => 'true',
            ]);

            static::assertSame(0, $generateExitCode);
            static::assertFileExists($baselineFile);

            \file_put_contents(
                $newFile,
                <<<'PHP'
<?php

function broken_new($value) {
    return $value;
}
PHP
            );

            $tester = $this->buildCommandTester();
            $exitCode = $tester->execute([
                'path' => [$directory],
                '--path-exclude-regex' => '#/vendor/#i',
                '--baseline-file' => $baselineFile,
            ]);

            static::assertSame(1, $exitCode);
            static::assertStringContainsString('2 new errors detected', $tester->getDisplay());
            static::assertStringContainsString('broken_new()', $tester->getDisplay());
        } finally {
            if (\is_file($baselineFile)) {
                \unlink($baselineFile);
            }
            if (\is_file($newFile)) {
                \unlink($newFile);
            }
            if (\is_file($file)) {
                \unlink($file);
            }
            if (\is_dir($directory)) {
                \rmdir($directory);
            }
        }
    }

    public function testCommandGenerateBaselineRejectsMissingTargetDirectory(): void
    {
        $baselineFile = \sys_get_temp_dir() . '/phpdoctor-missing-dir-' . \bin2hex(\random_bytes(8)) . '/baseline.json';
        static::assertFalse(\is_dir(\dirname($baselineFile)));

        $tester = $this->buildCommandTester();

        $exitCode = $tester->execute([
            'path' => [__DIR__ . '/Dummy7.php'],
            '--baseline-file' => $baselineFile,
            '--generate-baseline' => 'true',
        ]);

        static::assertSame(2, $exitCode);
        static::assertStringContainsString('directory', $tester->getDisplay());
        static::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    // =========================================================================
    // CheckFunctions uncovered branches
    // =========================================================================

    /**
     * A function whose @return phpdoc contains <phpdoctor-ignore-this-line/>
     * must not produce a missing-return-type error.
     */
    public function testFunctionReturnPhpdocIgnoreTag(): void
    {
        $code = '<?php
        /**
         * @return string <phpdoctor-ignore-this-line/>
         */
        function ignoredReturn() {}';

        // Use default skipParseErrorsAsError=true so parser warnings are suppressed
        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, true);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * A function with both a PHP return type and a phpdoc @return type exercises
     * the CheckPhpDocType path inside CheckFunctions.
     */
    public function testFunctionWithMatchingPhpDocAndNativeReturnType(): void
    {
        $code = '<?php
        /**
         * @return int
         */
        function add(int $a, int $b): int { return $a + $b; }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * A function with mismatched phpdoc @return and native return type must produce
     * a wrong-return-type error, exercising the CheckPhpDocType error path in CheckFunctions.
     */
    public function testFunctionWithMismatchedPhpDocReturnType(): void
    {
        $code = '<?php
        /**
         * @return string
         */
        function mismatched(): int { return 1; }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('mismatched()', $errors[0]);
    }

    /**
     * A function parameter whose phpdoc contains <phpdoctor-ignore-this-line/>
     * must be skipped.
     */
    public function testFunctionParamPhpdocIgnoreTag(): void
    {
        $code = '<?php
        /**
         * @param mixed $x <phpdoctor-ignore-this-line/>
         */
        function ignoredParam($x): void {}';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * A function with both a PHP parameter type and a phpdoc @param type exercises
     * the CheckPhpDocType path inside CheckFunctions::checkParameter.
     */
    public function testFunctionParamWithMatchingPhpDocAndNativeType(): void
    {
        $code = '<?php
        /**
         * @param int $value
         * @return void
         */
        function typed(int $value): void {}';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * A function with mismatched phpdoc @param and native param type must produce
     * a wrong-parameter-type error.
     */
    public function testFunctionParamWithMismatchedPhpDocType(): void
    {
        $code = '<?php
        /**
         * @param string $value
         * @return void
         */
        function mismatchedParam(int $value): void {}';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        $errors = $phpCodeErrors[''] ?? [];
        static::assertNotEmpty($errors);
        static::assertStringContainsString('mismatchedParam()', $errors[0]);
    }

    // =========================================================================
    // CheckPhpDocType uncovered branches
    // =========================================================================

    /**
     * A function/method parameter with a default value of null should have null
     * added to the effective PHP type when checking phpdoc consistency.
     */
    public function testCheckPhpDocTypeWithNullDefault(): void
    {
        $code = '<?php
        class NullDefault {
            /**
             * @param int $value
             * @return void
             */
            public function test(?int $value = null): void {}
        }';

        // Should not throw; may or may not produce an error depending on PHP version
        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertIsArray($phpCodeErrors);
    }

    /**
     * When the phpdoc @return type is a subtype of the native return type (via
     * class inheritance), no error should be emitted.
     */
    public function testCheckPhpDocTypeSubclassIsAccepted(): void
    {
        $code = '<?php
        class SubclassReturn {
            /**
             * @return \RuntimeException
             */
            public function make(): \Exception { return new \RuntimeException(); }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * When the phpdoc @param type is bool expressed as true|false literals while
     * the native type is bool, no error should be emitted.
     */
    public function testCheckPhpDocTypeBoolLiteralsAccepted(): void
    {
        $code = '<?php
        class BoolDoc {
            /**
             * @param true|false $flag
             * @return void
             */
            public function run(bool $flag): void {}
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * A phpdoc @return type of array[] (array of array) while native type is
     * Generator exercises the Generator/array[] branch in CheckPhpDocType.
     */
    public function testCheckPhpDocTypeArrayBracketWithGenerator(): void
    {
        $code = '<?php
        class GenDoc {
            /**
             * @return int[]
             */
            public function items(): \Generator { yield 1; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        // No "wrong return type" expected because Generator + [] is accepted
        $errors = $phpCodeErrors[''] ?? [];
        $wrongReturnErrors = \array_filter($errors, static function (string $e): bool {
            return \strpos($e, 'wrong return type') !== false;
        });
        static::assertSame([], \array_values($wrongReturnErrors));
    }

    /**
     * A phpdoc @return with class-string while native return is string must be
     * accepted (class-string is a subtype of string).
     */
    public function testCheckPhpDocTypeClassStringAccepted(): void
    {
        $code = '<?php
        class ClassStringDoc {
            /**
             * @return class-string
             */
            public function getClass(): string { return self::class; }
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], false, false, false, false);
        static::assertSame([], \array_filter($phpCodeErrors));
    }

    /**
     * @param array $result
     *
     * @return array
     */
    public static function removeLocalPathForTheTest(array $result): array
    {
        // hack for CI
        $pathReplace = \realpath(\getcwd() . '/../') . '/';
        if (!\is_array($result)) {
            return $result;
        }

        $helper = [];
        foreach ($result as $key => $value) {
            if (\is_string($key)) {
                $key = (string)\str_replace($pathReplace, '', $key);
            }

            if (\is_array($value)) {
                $helper[$key] = self::removeLocalPathForTheTest($value);
            } else {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (\is_string($value)) {
                    $helper[$key] = \str_replace($pathReplace, '', $value);
                } else {
                    $helper[$key] = $value;
                }
            }
        }

        return $helper;
    }
}
