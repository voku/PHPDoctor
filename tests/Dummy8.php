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
}
