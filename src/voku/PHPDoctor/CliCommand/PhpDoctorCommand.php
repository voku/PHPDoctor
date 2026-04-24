<?php

/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

namespace voku\PHPDoctor\CliCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use voku\PHPDoctor\PhpDocCheck\PhpCodeChecker;
use voku\PHPDoctor\QualityProfile;

final class PhpDoctorCommand extends Command
{
    public const COMMAND_NAME = 'analyse';
    public const ALIASES = ['analyze'];
    private const SUPPRESSIBLE_WRITE_ERROR_SEVERITIES = [\E_WARNING, \E_NOTICE, \E_USER_WARNING, \E_USER_NOTICE];

    /**
     * @var string[]
     */
    private $autoloaderProjectPaths = [];

    /**
     * @param string[] $autoloaderProjectPaths
     */
    public function __construct(array $autoloaderProjectPaths)
    {
        parent::__construct();

        $this->autoloaderProjectPaths = $autoloaderProjectPaths;
    }

    public function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Check PHP files or directories for missing types.')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputArgument('path', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The path to analyse'),
                    ]
                )
            )
            ->addOption(
                'autoload-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to your autoloader.',
                ''
            )
            ->addOption(
                'access',
                null,
                InputOption::VALUE_OPTIONAL,
                'Check for "public|protected|private" methods.',
                'public|protected|private'
            )
            ->addOption(
                'skip-ambiguous-types-as-error',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip check for ambiguous types. (false or true)',
                'false'
            )
            ->addOption(
                'skip-deprecated-functions',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip check for deprecated functions / methods. (false or true)',
                'false'
            )
            ->addOption(
                'skip-functions-with-leading-underscore',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip check for functions / methods with leading underscore. (false or true)',
                'false'
            )
            ->addOption(
                'skip-parse-errors',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip parse errors in the output. (false or true)',
                'true'
            )->addOption(
                'path-exclude-regex',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip some paths via regex e.g. "#/vendor/|/other/.*/path/#i"',
                '#/vendor/|/tests/#i'
             )->addOption(
                 'file-extensions',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'Check different file extensions e.g. ".php|.php4|.php5|.inc"',
                 '.php'
             )->addOption(
                 'profile',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'Show a type and PHPDoc quality profile summary. (false or true)',
                 'false'
             )->addOption(
                 'output-format',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'Output format for the analysis result. (text or json)',
                 'text'
             )->addOption(
                 'baseline-file',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'Compare against a PHPDoctor JSON baseline file so only new findings fail.',
                 ''
             )->addOption(
                 'generate-baseline',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'Write the current type and PHPDoc profile to --baseline-file. (false or true)',
                 'false'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $pathArray = $input->getArgument('path');
        if (!$pathArray) {
            // fallback
            $pathArray = ['.'];
        }
        \assert(\is_array($pathArray));
        foreach ($pathArray as $pathItem) {
            $realPath = \realpath($pathItem);
            \assert(\is_string($realPath));

            if (!$realPath || !\file_exists($realPath)) {
                $output->writeln('-------------------------------');
                $output->writeln('The path "' . $pathItem . '" does not exists.');
                $output->writeln('-------------------------------');

                return 2;
            }
        }

        $autoloadPath = $input->getOption('autoload-file');
        \assert(\is_string($autoloadPath) || $autoloadPath === null);
        if ($autoloadPath) {
            $autoloadRealPath = \realpath($autoloadPath);
            \assert(\is_string($autoloadRealPath));

            if (!$autoloadRealPath || !\file_exists($autoloadRealPath)) {
                $output->writeln('-------------------------------');
                $output->writeln('The autoload-file "' . $autoloadPath . '" does not exists.');
                $output->writeln('-------------------------------');

                return 2;
            }

            $this->autoloaderProjectPaths[] = $autoloadRealPath;
        }

        $access = $input->getOption('access');
        \assert(\is_string($access));
        $access = (array) \explode('|', $access);

        $skipAmbiguousTypesAsError = $input->getOption('skip-ambiguous-types-as-error') !== 'false';
        $skipDeprecatedFunctions = $input->getOption('skip-deprecated-functions') !== 'false';
        $skipFunctionsWithLeadingUnderscore = $input->getOption('skip-functions-with-leading-underscore') !== 'false';
        $skipParseErrorsAsError = $input->getOption('skip-parse-errors') !== 'false';

        $pathExcludeRegex = $input->getOption('path-exclude-regex');
        \assert(\is_string($pathExcludeRegex));

        $fileExtensions = $input->getOption('file-extensions');
        \assert(\is_string($fileExtensions));

        $profileSummaryEnabled = $input->getOption('profile') !== 'false';

        $outputFormat = $input->getOption('output-format');
        \assert(\is_string($outputFormat));
        if (!\in_array($outputFormat, ['text', 'json'], true)) {
            $output->writeln('-------------------------------');
            $output->writeln('The output-format "' . $outputFormat . '" is not supported. Use "text" or "json".');
            $output->writeln('-------------------------------');

            return 2;
        }

        $baselineFile = $input->getOption('baseline-file');
        \assert(\is_string($baselineFile));

        $generateBaseline = $input->getOption('generate-baseline') !== 'false';

        $baselineFingerprints = [];
        if (!$generateBaseline && $baselineFile !== '' && \is_file($baselineFile)) {
            $baselineProfile = self::readJsonFile($baselineFile);
            if ($baselineProfile === null) {
                $output->writeln('-------------------------------');
                $output->writeln('The baseline-file "' . $baselineFile . '" does not contain valid JSON.');
                $output->writeln('-------------------------------');

                return 2;
            }

            $baselineFingerprints = QualityProfile::fingerprintsFromProfile($baselineProfile);
        }

        $formatter = $output->getFormatter();
        $formatter->setStyle('file', new OutputFormatterStyle('default', null, ['bold']));
        $formatter->setStyle('error', new OutputFormatterStyle('red', null, []));

        if ($outputFormat === 'text') {
            $banner = \sprintf('List of errors in : %s', \implode(' | ', $pathArray));
            $output->writeln(\str_repeat('=', \strlen($banner)));
            $output->writeln($banner);
            $output->writeln(\str_repeat('=', \strlen($banner)));
            $output->writeln('');
        }

        $errors = PhpCodeChecker::checkPhpFiles(
            path: $pathArray,
            access: $access,
            skipAmbiguousTypesAsError: $skipAmbiguousTypesAsError,
            skipDeprecatedFunctions: $skipDeprecatedFunctions,
            skipFunctionsWithLeadingUnderscore: $skipFunctionsWithLeadingUnderscore,
            skipParseErrorsAsError: $skipParseErrorsAsError,
            autoloaderProjectPaths: $this->autoloaderProjectPaths,
            pathExcludeRegex: [$pathExcludeRegex],
            fileExtensions: \explode('|', $fileExtensions)
        );

        $qualityProfile = QualityProfile::fromErrors($errors, $baselineFingerprints);

        if ($generateBaseline) {
            if ($baselineFile === '') {
                $output->writeln('-------------------------------');
                $output->writeln('The --generate-baseline option requires --baseline-file.');
                $output->writeln('-------------------------------');

                return 2;
            }

            $baselineJson = self::jsonEncode($qualityProfile);
            $writeError = null;
            \set_error_handler(
                static function (int $severity, string $message) use (&$writeError): bool {
                    $writeError = '[' . self::errorSeverityToString($severity) . '] ' . $message;

                    return \in_array($severity, self::SUPPRESSIBLE_WRITE_ERROR_SEVERITIES, true);
                }
            );
            $writeResult = \file_put_contents($baselineFile, $baselineJson . "\n");
            \restore_error_handler();

            if ($writeResult === false) {
                $output->writeln('-------------------------------');
                $output->writeln('The baseline-file "' . $baselineFile . '" could not be written.');
                if ($writeError !== null) {
                    $output->writeln('Reason: ' . $writeError);
                }
                $output->writeln('-------------------------------');

                return 2;
            }

            $output->writeln('PHPDoctor baseline written to ' . $baselineFile . '.');

            return 0;
        }

        if ($outputFormat === 'json') {
            $output->writeln(self::jsonEncode($qualityProfile));

            return $qualityProfile['new_error_count'] > 0 ? 1 : 0;
        }

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
        if ($baselineFile !== '') {
            $output->writeln($qualityProfile['new_error_count'] . ' new errors detected.');
        }
        $output->writeln('-------------------------------');

        if ($profileSummaryEnabled || $baselineFile !== '') {
            $output->writeln('');
            $output->writeln('PHPDoctor type and PHPDoc quality profile');
            foreach ($qualityProfile['summary'] as $category => $count) {
                if ($count > 0) {
                    $output->writeln('- ' . $category . ': ' . $count);
                }
            }
        }

        return ($baselineFile !== '' ? $qualityProfile['new_error_count'] : $errorCount) > 0 ? 1 : 0;
    }

    /**
     * @return array{findings?: mixed}|null
     */
    private static function readJsonFile(string $file): ?array
    {
        if (!\is_readable($file)) {
            return null;
        }

        $contents = \file_get_contents($file);
        if (!\is_string($contents)) {
            return null;
        }

        $decoded = \json_decode($contents, true);
        if (!\is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param mixed $data
     */
    private static function jsonEncode($data): string
    {
        $json = \json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        if (!\is_string($json)) {
            throw new \RuntimeException('Unexpected internal failure encoding the PHPDoctor profile as JSON: ' . \json_last_error_msg());
        }

        return $json;
    }

    private static function errorSeverityToString(int $severity): string
    {
        return match ($severity) {
            \E_ERROR => 'E_ERROR',
            \E_WARNING => 'E_WARNING',
            \E_PARSE => 'E_PARSE',
            \E_NOTICE => 'E_NOTICE',
            \E_CORE_ERROR => 'E_CORE_ERROR',
            \E_CORE_WARNING => 'E_CORE_WARNING',
            \E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            \E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            \E_USER_ERROR => 'E_USER_ERROR',
            \E_USER_WARNING => 'E_USER_WARNING',
            \E_USER_NOTICE => 'E_USER_NOTICE',
            \E_STRICT => 'E_STRICT',
            \E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            \E_DEPRECATED => 'E_DEPRECATED',
            \E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'E_UNKNOWN_' . $severity,
        };
    }
}
