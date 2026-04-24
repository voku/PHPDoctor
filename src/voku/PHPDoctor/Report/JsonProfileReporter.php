<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Report;

use Symfony\Component\Console\Output\OutputInterface;

final class JsonProfileReporter
{
    /**
     * @param mixed $qualityProfile
     */
    public static function write(OutputInterface $output, mixed $qualityProfile): void
    {
        $output->writeln(self::jsonEncode($qualityProfile));
    }

    /**
     * @param mixed $data
     */
    private static function jsonEncode(mixed $data): string
    {
        return \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
