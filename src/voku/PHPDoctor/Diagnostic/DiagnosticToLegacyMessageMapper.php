<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class DiagnosticToLegacyMessageMapper
{
    public static function map(Diagnostic $diagnostic): string
    {
        return match ($diagnostic->id()) {
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG => '[' . ($diagnostic->line() ?? '?') . ']: missing @deprecated tag in phpdoc from ' . self::displayName($diagnostic),
            DiagnosticId::MISSING_NATIVE_PARAMETER_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: missing parameter type for ' . self::displayName($diagnostic) . ' | parameter:' . self::parameterName($diagnostic),
            DiagnosticId::MISSING_NATIVE_PROPERTY_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: missing property type for ' . self::displayName($diagnostic) . '->$' . self::propertyName($diagnostic),
            DiagnosticId::MISSING_NATIVE_RETURN_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: missing return type for ' . self::displayName($diagnostic),
            DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: missing parameter type "' . self::missingType($diagnostic) . '" in phpdoc from ' . self::displayName($diagnostic) . ' | parameter:' . self::parameterName($diagnostic),
            DiagnosticId::MISSING_PHPDOC_RETURN_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: missing return type "' . self::missingType($diagnostic) . '" in phpdoc from ' . self::displayName($diagnostic),
            DiagnosticId::PARSER_SYNTAX_ERROR => self::legacyMessage($diagnostic),
            DiagnosticId::WRONG_PHPDOC_PARAMETER_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: wrong parameter type "' . self::phpdocType($diagnostic) . '" in phpdoc from ' . self::displayName($diagnostic) . '  | parameter:' . self::parameterName($diagnostic),
            DiagnosticId::WRONG_PHPDOC_RETURN_TYPE => '[' . ($diagnostic->line() ?? '?') . ']: wrong return type "' . self::phpdocType($diagnostic) . '" in phpdoc from ' . self::displayName($diagnostic),
            default => throw new \InvalidArgumentException('Unsupported diagnostic id "' . $diagnostic->id() . '".'),
        };
    }

    private static function displayName(Diagnostic $diagnostic): string
    {
        $displayName = $diagnostic->evidence()['display_name'] ?? '?';

        return \is_string($displayName) ? $displayName : '?';
    }

    private static function legacyMessage(Diagnostic $diagnostic): string
    {
        $legacyMessage = $diagnostic->evidence()['legacy_message'] ?? '';

        return \is_string($legacyMessage) ? $legacyMessage : '';
    }

    private static function parameterName(Diagnostic $diagnostic): string
    {
        $parameterName = $diagnostic->evidence()['parameter_name'] ?? '?';

        return \is_string($parameterName) ? $parameterName : '?';
    }

    private static function missingType(Diagnostic $diagnostic): string
    {
        $missingType = $diagnostic->evidence()['missing_type'] ?? '?';

        return \is_string($missingType) ? $missingType : '?';
    }

    private static function propertyName(Diagnostic $diagnostic): string
    {
        $propertyName = $diagnostic->evidence()['property_name'] ?? '?';

        return \is_string($propertyName) ? $propertyName : '?';
    }

    private static function phpdocType(Diagnostic $diagnostic): string
    {
        $phpdocType = $diagnostic->evidence()['phpdoc_type'] ?? '?';

        return \is_string($phpdocType) ? $phpdocType : '?';
    }
}
