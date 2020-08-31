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
                    3 => '[44]: missing parameter type for voku\tests\Dummy3->lall() | parameter:foo',
                    4 => '[44]: missing return type for voku\tests\Dummy3->lall()',
                    5 => '[154]: missing parameter type "null" in phpdoc from voku\tests\Dummy3->lall8() | parameter:case',
                    6 => '[74]: wrong return type "string" in phpdoc from voku\tests\Dummy3->lall3()',
                    7 => '[64]: wrong return type "null" in phpdoc from voku\tests\Dummy3->lall2_1()',
                    8 => '[54]: missing return type "null" in phpdoc from voku\tests\Dummy3->lall2()',
                    9 => '[84]: wrong parameter type "string" in phpdoc from voku\tests\Dummy3->lall3_1()  | parameter:foo',
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
                        0 => '[12]: missing property type for voku\tests\Dummy5->$foo',
                        1 => '[12]: missing property type for voku\tests\Dummy5->$foo_mixed',
                        2 => '[12]: missing property type "int" in phpdoc from voku\tests\Dummy5 | property:foo_int_4',
                        3 => '[12]: wrong property type "string" in phpdoc from voku\tests\Dummy5  | property:foo_int_4',
                        4 => '[12]: missing property type "null" in phpdoc from voku\tests\Dummy5 | property:foo_int_6',
                        5 => '[12]: wrong property type "null" in phpdoc from voku\tests\Dummy5  | property:foo_int_7',
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

        $phpCodeErrors = PhpCodeChecker::checkPhpFiles(__DIR__ . '/Dummy9.php');

        $phpCodeErrors = self::removeLocalPathForTheTest($phpCodeErrors);

        static::assertSame([], $phpCodeErrors);
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
        }';

        $phpCodeErrors = PhpCodeChecker::checkFromString($code, ['public']);

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
                    0 => '[16]: missing parameter type for HelloWorld->sayHello2() | parameter:date',
                    1 => '[8]: missing parameter type for HelloWorld->sayHello() | parameter:date',
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
         * @param int $port [optional] <p>
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
         * @param float $timeout [optional] <p>
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
