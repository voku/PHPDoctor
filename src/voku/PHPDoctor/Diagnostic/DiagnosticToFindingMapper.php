<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

use voku\PHPDoctor\Finding\Finding;
use voku\PHPDoctor\Finding\FindingCategory;
use voku\PHPDoctor\Finding\FindingFingerprint;

final class DiagnosticToFindingMapper
{
    public static function map(Diagnostic $diagnostic): Finding
    {
        $category = self::category($diagnostic);
        $message = DiagnosticToLegacyMessageMapper::map($diagnostic);

        return self::mapWithMessage($diagnostic, $category, $message);
    }

    private static function mapWithMessage(
        Diagnostic $diagnostic,
        FindingCategory $category,
        string $message
    ): Finding {
        return new Finding(
            $diagnostic->file(),
            $diagnostic->line(),
            $category,
            $message,
            FindingFingerprint::fromDetails(
                $diagnostic->file(),
                $category,
                $diagnostic->line(),
                $message
            )
        );
    }

    private static function category(Diagnostic $diagnostic): FindingCategory
    {
        return match ($diagnostic->id()) {
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG => FindingCategory::fromValue(FindingCategory::DEPRECATED_DOCUMENTATION),
            DiagnosticId::MISSING_NATIVE_PARAMETER_TYPE => FindingCategory::fromValue(FindingCategory::MISSING_NATIVE_TYPE),
            DiagnosticId::MISSING_NATIVE_PROPERTY_TYPE => FindingCategory::fromValue(FindingCategory::MISSING_NATIVE_TYPE),
            DiagnosticId::MISSING_NATIVE_RETURN_TYPE => FindingCategory::fromValue(FindingCategory::MISSING_NATIVE_TYPE),
            DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE => FindingCategory::fromValue(FindingCategory::MISSING_PHPDOC_TYPE),
            DiagnosticId::PARSER_SYNTAX_ERROR => FindingCategory::fromValue(FindingCategory::PARSE_ERROR),
            default => throw new \InvalidArgumentException('Unsupported diagnostic id "' . $diagnostic->id() . '".'),
        };
    }
}
