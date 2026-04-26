<?php

declare(strict_types = 1);

namespace voku\tests;

#[\Attribute(
    \Attribute::TARGET_CLASS
    | \Attribute::TARGET_FUNCTION
    | \Attribute::TARGET_METHOD
    | \Attribute::TARGET_PROPERTY
    | \Attribute::TARGET_CLASS_CONSTANT
    | \Attribute::TARGET_CONSTANT
)]
final class Deprecated
{
    public function __construct(public ?string $message = null)
    {
    }
}
