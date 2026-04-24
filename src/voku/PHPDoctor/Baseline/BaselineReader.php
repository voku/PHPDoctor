<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Baseline;

final class BaselineReader
{
    /**
     * @throws \JsonException
     * @throws \UnexpectedValueException
     */
    public static function read(string $file): Baseline
    {
        if (!\is_readable($file)) {
            throw new \JsonException('The baseline file is not readable.');
        }

        $contents = \file_get_contents($file);
        if (!\is_string($contents)) {
            throw new \JsonException('The baseline file could not be read.');
        }

        $decoded = \json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new \UnexpectedValueException('The baseline file must decode to a JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        return self::fromArray($decoded);
    }

    /**
     * @param array<string, mixed> $baseline
     *
     * @throws \UnexpectedValueException
     */
    public static function fromArray(array $baseline): Baseline
    {
        if (isset($baseline['schema_version'])) {
            return self::fromVersionedBaseline($baseline);
        }

        return self::fromLegacyProfile($baseline);
    }

    /**
     * @param array<string, mixed> $baseline
     *
     * @throws \UnexpectedValueException
     */
    private static function fromVersionedBaseline(array $baseline): Baseline
    {
        if (
            !isset($baseline['schema_version'], $baseline['tool'], $baseline['scope'], $baseline['generated_at'], $baseline['findings'])
            || $baseline['schema_version'] !== Baseline::SCHEMA_VERSION
            || $baseline['tool'] !== Baseline::TOOL
            || $baseline['scope'] !== Baseline::SCOPE
            || !\is_string($baseline['generated_at'])
            || !\is_array($baseline['findings'])
        ) {
            throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
        }

        return new Baseline(
            $baseline['generated_at'],
            self::normalizeFindings($baseline['findings'], false)
        );
    }

    /**
     * @param array<string, mixed> $baseline
     *
     * @throws \UnexpectedValueException
     */
    private static function fromLegacyProfile(array $baseline): Baseline
    {
        if (!isset($baseline['findings']) || !\is_array($baseline['findings'])) {
            throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
        }

        return new Baseline(
            \gmdate('c'),
            self::normalizeFindings($baseline['findings'], true)
        );
    }

    /**
     * @param mixed[] $findings
     *
     * @return list<array{
     *     fingerprint: string,
     *     category: string,
     *     file: string,
     *     line: null|int
     * }>
     *
     * @throws \UnexpectedValueException
     */
    private static function normalizeFindings(array $findings, bool $legacy): array
    {
        $normalizedFindings = [];

        foreach ($findings as $finding) {
            if (!\is_array($finding) || !isset($finding['fingerprint']) || !\is_string($finding['fingerprint'])) {
                throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
            }

            $line = null;
            if (\array_key_exists('line', $finding)) {
                if (!\is_int($finding['line']) && $finding['line'] !== null) {
                    throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
                }

                $line = $finding['line'];
            }

            $file = '';
            if (\array_key_exists('file', $finding)) {
                if (!\is_string($finding['file'])) {
                    if (!$legacy) {
                        throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
                    }
                } else {
                    $file = $finding['file'];
                }
            }

            $category = '';
            if (\array_key_exists('category', $finding)) {
                if (!\is_string($finding['category'])) {
                    if (!$legacy) {
                        throw new \UnexpectedValueException('The baseline file does not contain a supported baseline schema.');
                    }
                } else {
                    $category = $finding['category'];
                }
            }

            $normalizedFindings[] = [
                'fingerprint' => $finding['fingerprint'],
                'category' => $category,
                'file' => $file,
                'line' => $line,
            ];
        }

        return $normalizedFindings;
    }
}
