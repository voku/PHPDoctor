<?php

declare(strict_types=1);

namespace voku\PHPDoctor;

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
     * @param array{findings?: mixed} $profile
     *
     * @return string[]
     */
    public static function fingerprintsFromProfile(array $profile): array
    {
        return TypedQualityProfile::fingerprintsFromProfile($profile);
    }
}
