<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Report;

use Symfony\Component\Console\Output\OutputInterface;

final class JsonProfileReporter
{
    /**
     * @param mixed $data
     */
    public static function write(OutputInterface $output, mixed $data): void
    {
        $output->writeln(self::jsonEncode($data));
    }

    /**
     * @param mixed $data
     */
    private static function jsonEncode(mixed $data): string
    {
        return \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
