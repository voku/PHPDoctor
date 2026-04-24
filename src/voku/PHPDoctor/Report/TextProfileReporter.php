<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Report;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

final class TextProfileReporter
{
    public static function configureStyles(OutputInterface $output): void
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle('file', new OutputFormatterStyle('default', null, ['bold']));
        $formatter->setStyle('error', new OutputFormatterStyle('red', null, []));
    }

    /**
     * @param string[] $pathArray
     */
    public static function writeBanner(OutputInterface $output, array $pathArray): void
    {
        $banner = \sprintf('List of errors in : %s', \implode(' | ', $pathArray));
        $output->writeln(\str_repeat('=', \strlen($banner)));
        $output->writeln($banner);
        $output->writeln(\str_repeat('=', \strlen($banner)));
        $output->writeln('');
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array{
     *     new_error_count: int,
     *     summary: array<string, int>
     * } $qualityProfile
     */
    public static function writeAnalysis(
        OutputInterface $output,
        array $errors,
        array $qualityProfile,
        bool $profileSummaryEnabled,
        bool $hasBaseline
    ): int {
        $errorCount = 0;
        foreach ($errors as $file => $errorsInner) {
            $errorCountFile = \count($errorsInner);
            $errorCount += $errorCountFile;

            $output->writeln('<file>' . $file . '</file>' . ' (' . $errorCountFile . ' errors)');

            foreach ($errorsInner as $errorInner) {
                $output->writeln('<error>' . $errorInner . '</error>');
            }

            /** @noinspection DisconnectedForeachInstructionInspection */
            $output->writeln('');
        }

        $output->writeln('-------------------------------');
        $output->writeln($errorCount . ' errors detected.');
        if ($hasBaseline) {
            $output->writeln($qualityProfile['new_error_count'] . ' new errors detected.');
        }
        $output->writeln('-------------------------------');

        if ($profileSummaryEnabled || $hasBaseline) {
            $output->writeln('');
            $output->writeln('PHPDoctor type and PHPDoc quality profile');
            foreach ($qualityProfile['summary'] as $category => $count) {
                if ($count > 0) {
                    $output->writeln('- ' . $category . ': ' . $count);
                }
            }
        }

        return $errorCount;
    }
}
