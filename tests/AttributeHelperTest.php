<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\PhpDocCheck\AttributeHelper;

/**
 * @internal
 */
final class AttributeHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testHasAttributeNamedMatchesShortAndQualifiedNamesCaseInsensitively(): void
    {
        $attributes = [
            (object) ['name' => 'Deprecated'],
            (object) ['name' => '\Vendor\Package\OtherAttribute'],
            (object) ['name' => '\Vendor\Package\CustomDeprecated'],
        ];

        static::assertTrue(AttributeHelper::hasAttributeNamed($attributes, 'deprecated'));
        static::assertTrue(AttributeHelper::hasAttributeNamed($attributes, 'otherattribute'));
        static::assertFalse(AttributeHelper::hasAttributeNamed($attributes, 'missingattribute'));
    }

    public function testHasAttributeNamedIgnoresAttributesWithoutStringNames(): void
    {
        $attributes = [
            (object) [],
            (object) ['name' => null],
            (object) ['name' => 123],
        ];

        static::assertFalse(AttributeHelper::hasAttributeNamed($attributes, 'deprecated'));
    }
}
