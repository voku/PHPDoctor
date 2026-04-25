<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Profile;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticToFindingMapper;
use voku\PHPDoctor\Finding\Finding;

final class QualityProfileBuilder
{
    /**
     * @param array<string, list<string>> $errors
     * @param string[]                    $baselineFingerprints
     */
    public static function fromErrors(array $errors, array $baselineFingerprints = []): QualityProfile
    {
        return self::fromFindings(self::findingsFromErrors($errors), $baselineFingerprints);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param string[]                    $baselineFingerprints
     *
     * Transitional compatibility path for callers that still pass legacy errors plus diagnostics.
     */
    public static function fromErrorsAndDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        array $baselineFingerprints = []
    ): QualityProfile {
        return self::fromFindings(
            DiagnosticToFindingMapper::mapAll($errors, $diagnostics),
            $baselineFingerprints
        );
    }

    /**
     * @param string[] $baselineFingerprints
     */
    public static function fromAnalysisResult(
        AnalysisResult $analysisResult,
        array $baselineFingerprints = []
    ): QualityProfile {
        return self::fromFindings($analysisResult->findings(), $baselineFingerprints);
    }

    /**
     * @param list<Finding> $findings
     * @param string[]      $baselineFingerprints
     */
    public static function fromFindings(array $findings, array $baselineFingerprints = []): QualityProfile
    {
        $baselineMap = \array_flip($baselineFingerprints);
        $newFindings = [];
        foreach ($findings as $finding) {
            if (!isset($baselineMap[$finding->fingerprint()->toString()])) {
                $newFindings[] = $finding;
            }
        }

        return new QualityProfile($findings, $newFindings);
    }

    /**
     * @param array<string, list<string>> $errors
     *
     * @return list<Finding>
     */
    private static function findingsFromErrors(array $errors): array
    {
        $findings = [];
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                $findings[] = Finding::fromMessage((string) $file, $message);
            }
        }

        return $findings;
    }
}
