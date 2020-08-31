<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
abstract class Dummy6 implements DummyInterface
{
    /**
     * @var null|int
     */
    protected $ResultRowCount = null;

    /**
     * @param int    $RowOffset
     * @param string $OrderByField
     * @param string $OrderByDir
     *
     * @return array<int, array<int|string, mixed>>
     */
    abstract public function getFieldArray($RowOffset, $OrderByField, $OrderByDir): array;

    /**
     * @return int|string
     */
    public function getRowCount()
    {
        return $this->ResultRowCount;
    }

    /**
     * @param \phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag
     *
     * @return array
     *
     * @psalm-return array{parsedParamTagStr: string, variableName: null[]|string}
     */
    public function withComplexReturnArray(\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag): array
    {
        return [
            'parsedParamTagStr' => 'foo',
            'variableName'      => [null],
        ];
    }
}
