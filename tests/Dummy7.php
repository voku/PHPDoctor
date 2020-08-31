<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
final class Dummy7 extends Dummy6
{
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
