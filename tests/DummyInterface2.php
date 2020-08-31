<?php

declare(strict_types=1);

namespace voku\tests;

/**
 * @internal
 */
interface DummyInterface2
{
    /**
     * @param null|\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag
     *
     * @return array
     *
     * @psalm-return array{parsedParamTagStr: string, variableName: null[]|string}
     */
    public function withComplexReturnArrayInheritDoc(?\phpDocumentor\Reflection\DocBlock\Tags\BaseTag $parsedParamTag);
}
