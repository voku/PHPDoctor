<?php

declare(strict_types=1);

namespace voku\tests;

use const SORT_ASC as SORT_ASC_TEST;

function foo3(int $foo = 0)
{
    return new Dummy();
}

const SORT_ASC_TEST_2 = SORT_ASC;

/**
 * @internal
 */
final class Dummy3 implements DummyInterface, DummyInterface2, DummyInterface3
{
    public const CASE_LOWER = \CASE_LOWER;

    public const CASE_SPECIAL = 123;

    public const CASE_NULL = null;

    public $foo;

    /**
     * @var mixed
     */
    public $foo_mixed;

    /**
     * @var mixed <phpdoctor-ignore-this-line/>
     */
    public $foo_mixed_v2;

    /**
     * @var int
     */
    public $foo_int;

    public ?Dummy $foo_dummy;

    /**
     * @var int<-2147483648,2147483647>
     */
    public $lall4;

    /**
     * @param $foo
     *
     * @return mixed
     */
    public function lall($foo)
    {
        return $foo + 1;
    }

    /**
     * @param int $foo
     *
     * @return int
     */
    public function lall2($foo): ?int
    {
        return $foo + 1;
    }

    /**
     * @param int $foo
     *
     * @return null|int
     */
    public function lall2_1($foo): int
    {
        return $foo + 1;
    }

    /**
     * @param int $foo
     *
     * @return int|string
     */
    public function lall3($foo): int
    {
        return $foo + 1;
    }

    /**
     * @param int|string $foo
     *
     * @return int
     */
    public function lall3_1(int $foo): int
    {
        return $foo + 1;
    }

    /**
     * @return \Generator|int[]
     */
    public function lall3_2(int $foo): \Generator
    {
        yield $foo;

        yield ++$foo;
    }

    /**
     * @return \Generator&int[]
     *
     * @psalm-return \Generator<int>
     */
    public function lall3_2_1(int $foo): \Generator
    {
        yield $foo;

        yield ++$foo;
    }

    /**
     * @return \voku\tests\Dummy3
     */
    public function lall4(): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall5(int $case = \CASE_LOWER): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall6(int $case = self::CASE_LOWER): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall7(int $case = self::CASE_SPECIAL): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall8(int $case = self::CASE_NULL): DummyInterface
    {
        return new self;
    }

    /**
     * @param null|int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall9(int $case = self::CASE_NULL): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall10(int $case = SORT_ASC_TEST): DummyInterface
    {
        return new self;
    }

    /**
     * @param int $case
     *
     * @return \voku\tests\Dummy3
     */
    public function lall11(int $case = SORT_ASC_TEST_2): DummyInterface
    {
        return new self;
    }

    /**
     * @param class-string $className
     *
     * @return DummyInterface
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function lall12(string $className): DummyInterface
    {
        return new self;
    }

    /**
     * This is a test-text [...] öäü !"§?.
     *
     * @param \phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag
     *                                                                        <p>this is a test-text [...] öäü !"§?</p>
     *
     * @return array
     *
     * @psalm-return array{parsedParamTagStr: string, variableName: null[]|string}
     */
    public function withComplexReturnArray(\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag)
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }

    /**
     * @inheritDoc
     */
    public function withComplexReturnArrayInheritDoc(?\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag)
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }

    /**
     * @inheritDoc
     */
    public function withComplexReturnArrayInheritDocWithDifferentVariableNames($param)
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }

    /**
     * @param int<-2147483648,2147483647> $intRange
     *
     * @return array
     *
     * @psalm-return array{intRange: int}
     */
    public static function withIntRange($intRange)
    {
        return ['int' => $intRange];
    }

    /**
     * Create a new Hub instance.
     *
     * @param  \Psr\Container\ContainerInterface|null  $container
     * @return void
     */
    public function __construct(\Psr\Container\ContainerInterface $container = null)
    {
    }

    /**
     * Create a new collection by invoking the callback a given amount of times.
     *
     * @param  int  $number
     * @param  null|self  $self
     * @return void
     */
    public static function self($number, ?self $self)
    {
        // ...
    }

    /**
     * @return array<int, class-string<Dummy>>
     */
    public static function withClassStringArray(): array
    {
        // ...

        return [
            0 => Dummy::class,
        ];
    }

    /**
     * @return array<int, class-string<\voku\tests\Dummy>>
     */
    public static function withClassStringArrayWithCorrectNamespace(): array
    {
        // ...

        return [];
    }
}
