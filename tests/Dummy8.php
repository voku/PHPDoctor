<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
final class Dummy8 extends Dummy6
{
    use DummyTrait;

    /**
     * {@inheritdoc}
     */
    public function getFieldArray($RowOffset, $OrderByField, $OrderByDir): array
    {
        return [
            ['foo' => 1],
            ['foo' => 2]
        ];
    }

    /**
     * @param array{stdClass: \stdClass, numbers: int|float} $lall
     *
     * @return array{stdClass: \stdClass, numbers: int|float}
     */
    public function foo_mixed($lall) {
        return $lall;
    }

    /**
     * @param array{stdClass: \stdClass, numbers: int|float $lall
     *
     * @return array{stdClass: \stdClass, numbers: int|float
     */
    public function foo_broken($lall) {
        return $lall;
    }

    /**
     * @param mixed $lall <p>this is mixed but it is ok, because ...</p> <phpdoctor-ignore-this-line/>
     *
     * @return array <phpdoctor-ignore-this-line/>
     */
    public function foo_ignore($lall) {
        return $lall;
    }
}
