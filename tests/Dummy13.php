<?php

declare(strict_types = 1);

namespace voku\tests;

/**
 * Test fixture for #[\Deprecated] attribute checks on class constants and
 * properties, which require a reflection-based workaround because the
 * underlying library's hasDeprecatedTag field is not populated for these
 * elements.
 */
class Dummy13
{
    /**
     * @deprecated use Dummy13::NEW_CONST instead
     */
    #[Deprecated(message: 'Use Dummy13::NEW_CONST instead')]
    const OLD_CONST = 'old';

    #[Deprecated(message: 'Missing phpdoc @deprecated tag')]
    const MISSING_DOC_CONST = 'missing';

    const NO_ATTR_CONST = 'none';

    /**
     * @deprecated use $newProp instead
     *
     * @var string
     */
    #[Deprecated(message: 'Use $newProp instead')]
    public string $oldProp = 'old';

    #[Deprecated(message: 'Missing phpdoc @deprecated tag')]
    public string $missingDocProp = 'missing';

    public string $noAttrProp = 'none';
}
