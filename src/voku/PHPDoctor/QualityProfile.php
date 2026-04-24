<?php

declare(strict_types=1);

namespace voku\PHPDoctor;

final class QualityProfile
{
    private const CATEGORY_MISSING_NATIVE_TYPE = 'missing_native_type';
    private const CATEGORY_MISSING_PHPDOC_TYPE = 'missing_phpdoc_type';
    private const CATEGORY_WRONG_PHPDOC_TYPE = 'wrong_phpdoc_type';
    private const CATEGORY_DEPRECATED_DOCUMENTATION = 'deprecated_documentation';
    private const CATEGORY_PARSE_ERROR = 'parse_error';
    private const CATEGORY_OVERRIDE_CONTRACT = 'override_contract';
    private const CATEGORY_OTHER = 'other';

    /**
     * @param string[][] $errors
     * @param string[]   $baselineFingerprints
     *
     * @return array{
     *     tool: string,
     *     scope: string,
     *     total_error_count: int,
     *     new_error_count: int,
     *     baseline_error_count: int,
     *     summary: array<string, int>,
     *     new_summary: array<string, int>,
     *     findings: list<array{file: string, line: null|int, category: string, message: string, fingerprint: string}>,
     *     new_findings: list<array{file: string, line: null|int, category: string, message: string, fingerprint: string}>
     * }
     */
    public static function fromErrors(array $errors, array $baselineFingerprints = []): array
    {
        $findings = [];
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                $findings[] = self::createFinding((string) $file, $message);
            }
        }

        $baselineMap = \array_flip($baselineFingerprints);
        $newFindings = [];
        foreach ($findings as $finding) {
            if (!isset($baselineMap[$finding['fingerprint']])) {
                $newFindings[] = $finding;
            }
        }

        return [
            'tool'                 => 'phpdoctor',
            'scope'                => 'type_and_phpdoc_quality',
            'total_error_count'    => \count($findings),
            'new_error_count'      => \count($newFindings),
            'baseline_error_count' => \count($findings) - \count($newFindings),
            'summary'              => self::summarizeFindings($findings),
            'new_summary'          => self::summarizeFindings($newFindings),
            'findings'             => $findings,
            'new_findings'         => $newFindings,
        ];
    }

    /**
     * @param array{findings?: mixed} $profile
     *
     * @return string[]
     */
    public static function fingerprintsFromProfile(array $profile): array
    {
        $fingerprints = [];
        if (!isset($profile['findings']) || !\is_array($profile['findings'])) {
            return $fingerprints;
        }

        foreach ($profile['findings'] as $finding) {
            if (
                \is_array($finding)
                &&
                isset($finding['fingerprint'])
                &&
                \is_string($finding['fingerprint'])
            ) {
                $fingerprints[] = $finding['fingerprint'];
            }
        }

        return \array_values(\array_unique($fingerprints));
    }

    /**
     * @return array{file: string, line: null|int, category: string, message: string, fingerprint: string}
     */
    private static function createFinding(string $file, string $message): array
    {
        $line = null;
        if (\preg_match('/^\[(\d+)\]: /', $message, $matches) === 1) {
            $line = (int) $matches[1];
        }

        return [
            'file'        => $file,
            'line'        => $line,
            'category'    => self::categorizeMessage($message),
            'message'     => $message,
            'fingerprint' => \sha1($file . "\0" . $message),
        ];
    }

    private static function categorizeMessage(string $message): string
    {
        if (\strpos($message, 'missing @deprecated tag') !== false) {
            return self::CATEGORY_DEPRECATED_DOCUMENTATION;
        }

        if (\strpos($message, 'invalid #[\Override] usage') !== false) {
            return self::CATEGORY_OVERRIDE_CONTRACT;
        }

        if (\stripos($message, 'parse') !== false || \stripos($message, 'syntax error') !== false) {
            return self::CATEGORY_PARSE_ERROR;
        }

        if (\preg_match('/wrong (property|parameter|return) type ".+" in phpdoc/', $message) === 1) {
            return self::CATEGORY_WRONG_PHPDOC_TYPE;
        }

        if (\preg_match('/missing (property|parameter|return) type ".+" in phpdoc/', $message) === 1) {
            return self::CATEGORY_MISSING_PHPDOC_TYPE;
        }

        if (\preg_match('/missing (property|parameter|return) type for /', $message) === 1) {
            return self::CATEGORY_MISSING_NATIVE_TYPE;
        }

        return self::CATEGORY_OTHER;
    }

    /**
     * @param list<array{category: string}> $findings
     *
     * @return array<string, int>
     */
    private static function summarizeFindings(array $findings): array
    {
        $summary = [
            self::CATEGORY_MISSING_NATIVE_TYPE       => 0,
            self::CATEGORY_MISSING_PHPDOC_TYPE       => 0,
            self::CATEGORY_WRONG_PHPDOC_TYPE         => 0,
            self::CATEGORY_DEPRECATED_DOCUMENTATION  => 0,
            self::CATEGORY_PARSE_ERROR               => 0,
            self::CATEGORY_OVERRIDE_CONTRACT         => 0,
            self::CATEGORY_OTHER                     => 0,
        ];

        foreach ($findings as $finding) {
            $summary[$finding['category']] = ($summary[$finding['category']] ?? 0) + 1;
        }

        return $summary;
    }
}
