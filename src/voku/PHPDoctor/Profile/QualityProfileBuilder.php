<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Profile;

use voku\PHPDoctor\Finding\Finding;

final class QualityProfileBuilder
{
    /**
     * @param array<string, list<string>> $errors
     * @param string[]                    $baselineFingerprints
     */
    public static function fromErrors(array $errors, array $baselineFingerprints = []): QualityProfile
    {
        $findings = [];
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                $findings[] = Finding::fromMessage((string) $file, $message);
            }
        }

        $baselineMap = \array_flip($baselineFingerprints);
        $newFindings = [];
        foreach ($findings as $finding) {
            if (!isset($baselineMap[$finding->fingerprint()->toString()])) {
                $newFindings[] = $finding;
            }
        }

        return new QualityProfile($findings, $newFindings);
    }
}
