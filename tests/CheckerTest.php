<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\PhpDocCheck\PhpCodeChecker;

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
                    0 => '[9]: missing return type for voku\tests\foo3()',
                    1 => '[19]: missing property type for voku\tests\Dummy3->$foo',
                    2 => '[19]: missing property type for voku\tests\Dummy3->$foo_mixed',
                    3 => '[56]: missing parameter type for voku\tests\Dummy3->lall() | parameter:foo',
                    4 => '[56]: missing return type for voku\tests\Dummy3->lall()',
                    5 => '[66]: missing return type "null" in phpdoc from voku\tests\Dummy3->lall2()',
                    6 => '[76]: wrong return type "null" in phpdoc from voku\tests\Dummy3->lall2_1()',
                    7 => '[86]: wrong return type "string" in phpdoc from voku\tests\Dummy3->lall3()',
                    8 => '[96]: wrong parameter type "string" in phpdoc from voku\tests\Dummy3->lall3_1()  | parameter:foo',
                    9 => '[116]: missing return type "Generator" in phpdoc from voku\tests\Dummy3->lall3_2_1()',
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
                        9  => '[89]: wrong parameter type "int" in phpdoc from voku\tests\Dummy10->test14()  | parameter:param1',
                        10 => '[89]: wrong return type "bool" in phpdoc from voku\tests\Dummy10->test14()',
                        11 => '[98]: wrong parameter type "int" in phpdoc from voku\tests\Dummy10->test141()  | parameter:param1',
                        12 => '[98]: wrong return type "bool" in phpdoc from voku\tests\Dummy10->test141()',
                        13 => '[107]: missing parameter type "float" in phpdoc from voku\tests\Dummy10->test142() | parameter:param1',
                        14 => '[107]: missing return type "int" in phpdoc from voku\tests\Dummy10->test142()',
                        15 => '[107]: wrong parameter type "int" in phpdoc from voku\tests\Dummy10->test142()  | parameter:param1',
                        16 => '[107]: wrong return type "bool" in phpdoc from voku\tests\Dummy10->test142()',
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
                        2 => '[40]: missing parameter type for voku\tests\Dummy11->sayHello() | parameter:date',
                    ],
                ], $phpCodeErrors
            );

            // --------------------------

        }
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
        function fsockopen ($hostname, $port = null, &$errno = null, &$errstr = null, $timeout = null) { /** ... */ };';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public'], true);

        if (\PHP_VERSION_ID <= 70199) {
            static::assertSame(
                [
                    '' => [
                        '[44]: missing parameter type for fsockopen() | parameter:errno',
                        '[44]: missing parameter type for fsockopen() | parameter:errstr',
                    ],
                ],
                $phpCodeErrors
            );
        } else {
            static::assertSame(
                [],
                $phpCodeErrors
            );
        }
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
                $key = (string) \str_replace($pathReplace, '', $key);
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
