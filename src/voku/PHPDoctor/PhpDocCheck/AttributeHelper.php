<?php

declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

/**
 * @internal
 */
final class AttributeHelper
{
    /**
     * @param array<int, object> $attributes
     */
    public static function hasAttributeNamed(array $attributes, string $attributeName): bool
    {
        $attributeName = \strtolower($attributeName);

        foreach ($attributes as $attribute) {
            $name = $attribute->name ?? null;
            if (!\is_string($name)) {
                continue;
            }

            $name = \ltrim($name, '\\');
            $shortName = \strrchr($name, '\\');
            if ($shortName !== false) {
                $name = \substr($shortName, 1);
            }

            if (\strtolower($name) === $attributeName) {
                return true;
            }
        }

        return false;
    }
}
