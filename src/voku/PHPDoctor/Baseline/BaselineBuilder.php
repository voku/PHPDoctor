<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Baseline;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Finding\Finding;

final class BaselineBuilder
{
    /**
     * @param array<string, list<string>> $errors
     */
    public static function fromErrors(array $errors): Baseline
    {
        return self::fromFindings(self::findingsFromErrors($errors));
    }

    public static function fromAnalysisResult(AnalysisResult $analysisResult): Baseline
    {
        return self::fromFindings($analysisResult->findings());
    }

    /**
     * @param list<Finding> $findings
     */
    public static function fromFindings(array $findings): Baseline
    {
        return new Baseline(
            \gmdate('c'),
            \array_map(
                static fn (Finding $finding): array => [
                    'fingerprint' => $finding->fingerprint()->toString(),
                    'category' => $finding->category()->value(),
                    'file' => $finding->file(),
                    'line' => $finding->line(),
                ],
                $findings
            )
        );
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
