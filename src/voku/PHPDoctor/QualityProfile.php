<?php

declare(strict_types=1);

namespace voku\PHPDoctor;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Profile\QualityProfile as TypedQualityProfile;
use voku\PHPDoctor\Profile\QualityProfileBuilder;

final class QualityProfile
{
    /**
     * @param array<string, list<string>> $errors
     * @param string[]                    $baselineFingerprints
     *
     * @return array{
     *     tool: string,
     *     scope: string,
     *     total_error_count: int,
     *     new_error_count: int,
     *     baseline_error_count: int,
     *     summary: array<string, int>,
     *     new_summary: array<string, int>,
     *     findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>,
     *     new_findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>
     * }
     */
    public static function fromErrors(array $errors, array $baselineFingerprints = []): array
    {
        return QualityProfileBuilder::fromErrors($errors, $baselineFingerprints)->toArray();
    }

    /**
     * @param string[] $baselineFingerprints
     *
     * @return array{
     *     tool: string,
     *     scope: string,
     *     total_error_count: int,
     *     new_error_count: int,
     *     baseline_error_count: int,
     *     summary: array<string, int>,
     *     new_summary: array<string, int>,
     *     findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>,
     *     new_findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>
     * }
     */
    public static function fromAnalysisResult(
        AnalysisResult $analysisResult,
        array $baselineFingerprints = []
    ): array {
        return QualityProfileBuilder::fromAnalysisResult($analysisResult, $baselineFingerprints)->toArray();
    }

    /**
     * @param array<string, list<string>> $errors
     * @param string[]                    $baselineFingerprints
     *
     * Transitional compatibility path for callers that still pass legacy errors plus diagnostics.
     *
     * @return array{
     *     tool: string,
     *     scope: string,
     *     total_error_count: int,
     *     new_error_count: int,
     *     baseline_error_count: int,
     *     summary: array<string, int>,
     *     new_summary: array<string, int>,
     *     findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>,
     *     new_findings: list<array{
     *         file: string,
     *         line: null|int,
     *         category: string,
     *         message: string,
     *         fingerprint: string
     *     }>
     * }
     */
    public static function fromErrorsAndDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        array $baselineFingerprints = []
    ): array {
        return QualityProfileBuilder::fromErrorsAndDiagnostics($errors, $diagnostics, $baselineFingerprints)->toArray();
    }

    /**
     * @param array{findings?: mixed} $profile
     *
     * @return string[]
     */
    public static function fingerprintsFromProfile(array $profile): array
    {
        return TypedQualityProfile::fingerprintsFromProfile($profile);
    }
}
