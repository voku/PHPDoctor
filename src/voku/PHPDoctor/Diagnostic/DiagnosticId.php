<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class DiagnosticId
{
    public const DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG = 'deprecated_attribute_missing_phpdoc_tag';
    public const PARSER_SYNTAX_ERROR = 'parser_syntax_error';

    private function __construct()
    {
        // no instances
    }
}
