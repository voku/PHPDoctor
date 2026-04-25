<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class DiagnosticId
{
    public const DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG = 'deprecated_attribute_missing_phpdoc_tag';
    public const MISSING_NATIVE_PARAMETER_TYPE = 'missing_native_parameter_type';
    public const MISSING_NATIVE_PROPERTY_TYPE = 'missing_native_property_type';
    public const MISSING_NATIVE_RETURN_TYPE = 'missing_native_return_type';
    public const MISSING_PHPDOC_PARAMETER_TYPE = 'missing_phpdoc_parameter_type';
    public const PARSER_SYNTAX_ERROR = 'parser_syntax_error';

    private function __construct()
    {
        // no instances
    }
}
