<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @return Dummy
 */
function foo(int $foo = 0)
{
    return new Dummy();
}

/**
 * @internal
 *
 * @property int $foo
 * @property string $bar
 */
class Dummy extends \stdClass
{
    const FOO1 = [1, 2];

    const FOO2 = 'lall';

    const FOO3 = 3;

    /**
     * @var null|int[]
     *
     * @phpstan-var null|array<int,int>
     */
    public $lall1 = [];

    /**
     * @var float
     */
    public $lall2 = 0.1;

    /**
     * @var null|float
     */
    public $lall3;

    const FOO_BAR = 4;

    /**
     * @return array<int, int>
     */
    public function withReturnType(): array
    {
        return [1, 2, 3];
    }

    /**
     * @return false|int
     */
    public function withoutReturnType()
    {
        return \random_int(0, 10) > 5 ? 0 : false;
    }

    /**
     * @return int[]|string[]|null <p>foo</p>
     *
     * @psalm-return ?list<int|string>
     */
    public function withoutPhpDocParam(bool $useRandInt = true)
    {
        return \random_int(0, 10) > 5 ? [1, 2, 'lall'] : null;
    }

    /**
     * @param int[]|null $useRandInt
     *
     * @psalm-param ?list<int> $useRandInt
     *                                    <p>foo öäü bar</p>
     */
    public function withPhpDocParam($useRandInt = [3, 5])
    {
        $max = $useRandInt === null ? 5 : \max($useRandInt);

        return \random_int(0, $max) > 2 ? [1, 2, 'lall'] : null;
    }

    /**
     * @psalm-param ?list<int> $useRandInt
     */
    public function withPsalmPhpDocOnlyParam($useRandInt = [3, 5])
    {
        $max = $useRandInt === null ? 5 : \max($useRandInt);

        return \random_int(0, $max) > 2 ? [1, 2, 'lall'] : null;
    }

    /**
     * @param \phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag
     *
     * @return array
     *
     * @psalm-return array{parsedParamTagStr: string, variableName: null[]|string}
     */
    public static function withComplexReturnArray(\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag)
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }

    /**
     * @param $parsedParamTag
     *
     * @return array
     *
     * @psalm-return array{parsedParamTagStr: string, variableName: null[]|string}
     */
    public static function withEmptyParamTypePhpDoc($parsedParamTag)
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }

    /**
     * @param mixed $p1
     * @param mixed $p2
     * @param mixed $p3
     *
     * @return array
     */
    public function withConstFromClass($p1 = self::FOO1, $p2 = self::FOO2, $p3 = self::FOO3)
    {
        return [];
    }
}
