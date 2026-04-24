<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Baseline;

final class Baseline
{
    public const SCHEMA_VERSION = 1;

    public const TOOL = 'phpdoctor';

    public const SCOPE = 'type_and_phpdoc_quality';

    private readonly string $generatedAt;

    /**
     * @var list<array{
     *     fingerprint: string,
     *     category: string,
     *     file: string,
     *     line: null|int
     * }>
     */
    private readonly array $findings;

    /**
     * @param list<array{
     *     fingerprint: string,
     *     category: string,
     *     file: string,
     *     line: null|int
     * }> $findings
     */
    public function __construct(string $generatedAt, array $findings)
    {
        $this->generatedAt = $generatedAt;
        $this->findings = $findings;
    }

    /**
     * @return array{
     *     schema_version: 1,
     *     tool: string,
     *     scope: string,
     *     generated_at: string,
     *     findings: list<array{
     *         fingerprint: string,
     *         category: string,
     *         file: string,
     *         line: null|int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'tool' => self::TOOL,
            'scope' => self::SCOPE,
            'generated_at' => $this->generatedAt,
            'findings' => $this->findings,
        ];
    }

    /**
     * @return string[]
     */
    public function fingerprints(): array
    {
        return \array_values(
            \array_unique(
                \array_map(
                    static fn (array $finding): string => $finding['fingerprint'],
                    $this->findings
                )
            )
        );
    }
}
