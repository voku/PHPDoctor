<?php

declare(strict_types=1);

namespace voku\tests;

use voku\tests\Dummy6 as DummyFoo;

/**
 * @internal
 */
final class Dummy9 extends DummyFoo
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
}
