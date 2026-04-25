<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Report;

use Symfony\Component\Console\Output\OutputInterface;

final class GithubActionsReporter
{
    /**
     * @param array{
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
     * } $qualityProfile
     */
    public static function write(OutputInterface $output, array $qualityProfile, bool $hasBaseline): void
    {
        $findings = $hasBaseline ? $qualityProfile['new_findings'] : $qualityProfile['findings'];

        foreach ($findings as $finding) {
            $output->writeln(self::formatErrorAnnotation($finding));
        }
    }

    /**
     * @param array{
     *     file: string,
     *     line: null|int,
     *     category: string,
     *     message: string,
     *     fingerprint: string
     * } $finding
     */
    private static function formatErrorAnnotation(array $finding): string
    {
        $properties = [];

        if ($finding['file'] !== '') {
            $properties[] = 'file=' . self::escapeWorkflowValue($finding['file']);
        }

        if ($finding['line'] !== null) {
            $properties[] = 'line=' . self::escapeWorkflowValue((string) $finding['line']);
        }

        $annotation = '::error';
        if ($properties !== []) {
            $annotation .= ' ' . \implode(',', $properties);
        }

        return $annotation . '::' . self::escapeWorkflowValue(
            $finding['message'] . ' (' . $finding['category'] . ')'
        );
    }

    private static function escapeWorkflowValue(string $value): string
    {
        return \str_replace(
            ['%', "\r", "\n", ':', ','],
            ['%25', '%0D', '%0A', '%3A', '%2C'],
            $value
        );
    }
}
