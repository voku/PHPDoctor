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
use voku\PHPDoctor\Autoload\ComposerAutoloaderLoader;
use voku\PHPDoctor\Baseline\BaselineFlow;
use voku\PHPDoctor\Baseline\BaselineFlowException;
use voku\PHPDoctor\PhpDocCheck\PhpCodeChecker;
use voku\PHPDoctor\QualityProfile;
use voku\PHPDoctor\Report\GithubActionsReporter;
use voku\PHPDoctor\Report\JsonProfileReporter;
use voku\PHPDoctor\Report\TextProfileReporter;

final class PhpDoctorCommand extends Command
{
    public const COMMAND_NAME = 'analyse';
    public const ALIASES = ['analyze'];

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
                 'Check pipe-delimited file extensions e.g. ".php|.php4|.php5|.inc"',
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
                  'Output format for the analysis result. (text, json or github)',
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
        $pathArgument = $input->getArgument('path');
        if ($pathArgument === null || $pathArgument === []) {
            $pathArray = ['.'];
        } elseif (\is_array($pathArgument)) {
            $pathArray = [];
            foreach ($pathArgument as $pathItem) {
                if (!\is_string($pathItem)) {
                    throw new \LogicException('The "path" argument must contain only strings.');
                }

                $pathArray[] = $pathItem;
            }
        } else {
            throw new \LogicException('The "path" argument must resolve to an array of strings.');
        }

        foreach ($pathArray as $pathItem) {
            $realPath = \realpath($pathItem);

            if (!$realPath || !\file_exists($realPath)) {
                $output->writeln('-------------------------------');
                $output->writeln('The path "' . $pathItem . '" does not exists.');
                $output->writeln('-------------------------------');

                return 2;
            }
        }

        $autoloadPath = self::stringOption($input, 'autoload-file');
        if ($autoloadPath !== '') {
            $autoloadRealPath = \realpath($autoloadPath);

            if (!$autoloadRealPath || !\file_exists($autoloadRealPath)) {
                $output->writeln('-------------------------------');
                $output->writeln('The autoload-file "' . $autoloadPath . '" does not exists.');
                $output->writeln('-------------------------------');

                return 2;
            }

            ComposerAutoloaderLoader::requireOnceIfNeeded($autoloadRealPath);
        }

        $access = self::parsePipeSeparatedList(self::stringOption($input, 'access'));

        $skipAmbiguousTypesAsError = self::stringOption($input, 'skip-ambiguous-types-as-error') !== 'false';
        $skipDeprecatedFunctions = self::stringOption($input, 'skip-deprecated-functions') !== 'false';
        $skipFunctionsWithLeadingUnderscore = self::stringOption($input, 'skip-functions-with-leading-underscore') !== 'false';
        $skipParseErrorsAsError = self::stringOption($input, 'skip-parse-errors') !== 'false';

        $pathExcludeRegexOption = self::stringOption($input, 'path-exclude-regex');
        $pathExcludeRegex = self::normalizeRegexOption($pathExcludeRegexOption);
        if ($pathExcludeRegex === null) {
            $output->writeln('-------------------------------');
            $output->writeln('The path-exclude-regex "' . $pathExcludeRegexOption . '" is not a valid regular expression.');
            $output->writeln('-------------------------------');

            return 2;
        }

        $fileExtensions = self::parsePipeSeparatedList(self::stringOption($input, 'file-extensions'));

        $profileSummaryEnabled = self::stringOption($input, 'profile') !== 'false';

        $outputFormat = self::stringOption($input, 'output-format');
        if (!\in_array($outputFormat, ['text', 'json', 'github'], true)) {
            $output->writeln('-------------------------------');
            $output->writeln('The output-format "' . $outputFormat . '" is not supported. Use "text", "json" or "github".');
            $output->writeln('-------------------------------');

            return 2;
        }

        $baselineFile = self::stringOption($input, 'baseline-file');

        $generateBaseline = self::stringOption($input, 'generate-baseline') !== 'false';

        $baselineFingerprints = [];
        if (!$generateBaseline && $baselineFile !== '') {
            try {
                $baselineFingerprints = BaselineFlow::loadFingerprints($baselineFile);
            } catch (BaselineFlowException $exception) {
                $output->writeln('-------------------------------');
                $output->writeln($exception->getMessage());
                $output->writeln('-------------------------------');

                return 2;
            }
        }

        if ($outputFormat === 'text') {
            TextProfileReporter::configureStyles($output);
            TextProfileReporter::writeBanner($output, $pathArray);
        }

        $analysisResult = PhpCodeChecker::analyseFiles(
            path: $pathArray,
            access: $access,
            skipAmbiguousTypesAsError: $skipAmbiguousTypesAsError,
            skipDeprecatedFunctions: $skipDeprecatedFunctions,
            skipFunctionsWithLeadingUnderscore: $skipFunctionsWithLeadingUnderscore,
            skipParseErrorsAsError: $skipParseErrorsAsError,
            autoloaderProjectPaths: [],
            pathExcludeRegex: $pathExcludeRegex === '' ? [] : [$pathExcludeRegex],
            fileExtensions: $fileExtensions
        );
        $errors = $analysisResult->toLegacyErrors();

        $qualityProfile = QualityProfile::fromAnalysisResult($analysisResult, $baselineFingerprints);

        if ($generateBaseline) {
            if ($baselineFile === '') {
                $output->writeln('-------------------------------');
                $output->writeln('The --generate-baseline option requires --baseline-file.');
                $output->writeln('-------------------------------');

                return 2;
            }

            try {
                BaselineFlow::generateFromAnalysisResult($baselineFile, $analysisResult);
            } catch (BaselineFlowException $exception) {
                $output->writeln('-------------------------------');
                $output->writeln($exception->getMessage());
                $output->writeln('-------------------------------');

                return 2;
            }

            $output->writeln('PHPDoctor baseline written to ' . $baselineFile . '.');

            return 0;
        }

        if ($outputFormat === 'json') {
            JsonProfileReporter::write($output, $qualityProfile);

            return $qualityProfile['new_error_count'] > 0 ? 1 : 0;
        }

        if ($outputFormat === 'github') {
            GithubActionsReporter::write($output, $qualityProfile, $baselineFile !== '');

            return $qualityProfile['new_error_count'] > 0 ? 1 : 0;
        }

        $errorCount = TextProfileReporter::writeAnalysis(
            $output,
            $errors,
            $qualityProfile,
            $profileSummaryEnabled,
            $baselineFile !== ''
        );

        return ($baselineFile !== '' ? $qualityProfile['new_error_count'] : $errorCount) > 0 ? 1 : 0;
    }

    /**
     * @return string[]
     */
    private static function parsePipeSeparatedList(string $value): array
    {
        return \array_values(
            \array_filter(
                \array_map(
                    static fn (string $item): string => \trim($item),
                    \explode('|', $value)
                ),
                static fn (string $item): bool => $item !== ''
            )
        );
    }

    private static function normalizeRegexOption(string $value): ?string
    {
        $value = \trim($value);
        if ($value === '') {
            return '';
        }

        if (self::isValidRegex($value)) {
            return $value;
        }

        foreach (['#', '~', '%', '!', ';', '@'] as $delimiter) {
            if (\strpos($value, $delimiter) !== false) {
                continue;
            }

            $wrapped = $delimiter . $value . $delimiter;
            if (self::isValidRegex($wrapped)) {
                return $wrapped;
            }
        }

        return null;
    }

    private static function isValidRegex(string $value): bool
    {
        return @\preg_match($value, '') !== false;
    }

    private static function stringOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);
        if (\is_string($value)) {
            return $value;
        }

        if ($value === null) {
            return '';
        }

        throw new \LogicException('The option "' . $name . '" must resolve to a string.');
    }
}
