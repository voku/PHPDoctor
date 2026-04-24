<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Profile;

use voku\PHPDoctor\Finding\Finding;
use voku\PHPDoctor\Finding\FindingCategory;

final class QualityProfile
{
    /**
     * @var list<Finding>
     */
    private readonly array $findings;

    /**
     * @var list<Finding>
     */
    private readonly array $newFindings;

    /**
     * @param list<Finding> $findings
     * @param list<Finding> $newFindings
     */
    public function __construct(array $findings, array $newFindings)
    {
        $this->findings = $findings;
        $this->newFindings = $newFindings;
    }

    /**
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
    public function toArray(): array
    {
        $findings = \array_map(
            static fn (Finding $finding): array => $finding->toArray(),
            $this->findings
        );
        $newFindings = \array_map(
            static fn (Finding $finding): array => $finding->toArray(),
            $this->newFindings
        );

        return [
            'tool' => 'phpdoctor',
            'scope' => 'type_and_phpdoc_quality',
            'total_error_count' => \count($findings),
            'new_error_count' => \count($newFindings),
            'baseline_error_count' => \count($findings) - \count($newFindings),
            'summary' => self::summarizeFindings($this->findings),
            'new_summary' => self::summarizeFindings($this->newFindings),
            'findings' => $findings,
            'new_findings' => $newFindings,
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
     * @param list<Finding> $findings
     *
     * @return array<string, int>
     */
    private static function summarizeFindings(array $findings): array
    {
        $summary = FindingCategory::summaryTemplate();

        foreach ($findings as $finding) {
            $summary[$finding->category()->value()]++;
        }

        return $summary;
    }
}
