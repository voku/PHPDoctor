<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class DiagnosticToLegacyMessageMapper
{
    public static function map(Diagnostic $diagnostic): string
    {
        return match ($diagnostic->id()) {
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG => '[' . ($diagnostic->line() ?? '?') . ']: missing @deprecated tag in phpdoc from ' . self::displayName($diagnostic),
            DiagnosticId::PARSER_SYNTAX_ERROR => self::legacyMessage($diagnostic),
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
}
