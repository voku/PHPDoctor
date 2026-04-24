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

    /**
     * @param array<string, list<string>> $errors
     *
     * @return list<Finding>
     */
    public static function mapAll(array $errors, DiagnosticCollection $diagnostics): array
    {
        $diagnosticFindings = [];
        foreach ($diagnostics->all() as $diagnostic) {
            $category = self::category($diagnostic);
            $message = DiagnosticToLegacyMessageMapper::map($diagnostic);
            $diagnosticFindings[$diagnostic->file()][$message][] = self::mapWithMessage(
                $diagnostic,
                $category,
                $message
            );
        }

        $findings = [];
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                if (isset($diagnosticFindings[$file][$message][0])) {
                    /** @var Finding $finding */
                    $finding = \array_shift($diagnosticFindings[$file][$message]);
                    $findings[] = $finding;

                    if ($diagnosticFindings[$file][$message] === []) {
                        unset($diagnosticFindings[$file][$message]);
                    }

                    continue;
                }

                $findings[] = Finding::fromMessage((string) $file, $message);
            }
        }

        foreach ($diagnosticFindings as $findingsByMessage) {
            foreach ($findingsByMessage as $findingsForMessage) {
                foreach ($findingsForMessage as $finding) {
                    $findings[] = $finding;
                }
            }
        }

        return $findings;
    }

    private static function category(Diagnostic $diagnostic): FindingCategory
    {
        return match ($diagnostic->id()) {
            DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG => FindingCategory::fromValue(FindingCategory::DEPRECATED_DOCUMENTATION),
            DiagnosticId::PARSER_SYNTAX_ERROR => FindingCategory::fromValue(FindingCategory::PARSE_ERROR),
            default => throw new \InvalidArgumentException('Unsupported diagnostic id "' . $diagnostic->id() . '".'),
        };
    }
}
