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
use voku\PHPDoctor\Baseline\BaselineFlow;
use voku\PHPDoctor\Baseline\BaselineFlowException;
use voku\PHPDoctor\PhpDocCheck\PhpCodeChecker;
use voku\PHPDoctor\QualityProfile;
use voku\PHPDoctor\Report\JsonProfileReporter;
use voku\PHPDoctor\Report\TextProfileReporter;

final class PhpDoctorCommand extends Command
{
    public const COMMAND_NAME = 'analyse';
    public const ALIASES = ['analyze'];

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
        $fileExtensions = self::parsePipeSeparatedList($fileExtensions);

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

        $errors = PhpCodeChecker::checkPhpFiles(
            path: $pathArray,
            access: $access,
            skipAmbiguousTypesAsError: $skipAmbiguousTypesAsError,
            skipDeprecatedFunctions: $skipDeprecatedFunctions,
            skipFunctionsWithLeadingUnderscore: $skipFunctionsWithLeadingUnderscore,
            skipParseErrorsAsError: $skipParseErrorsAsError,
            autoloaderProjectPaths: $this->autoloaderProjectPaths,
            pathExcludeRegex: [$pathExcludeRegex],
            fileExtensions: $fileExtensions
        );

        $qualityProfile = QualityProfile::fromErrors($errors, $baselineFingerprints);

        if ($generateBaseline) {
            if ($baselineFile === '') {
                $output->writeln('-------------------------------');
                $output->writeln('The --generate-baseline option requires --baseline-file.');
                $output->writeln('-------------------------------');

                return 2;
            }

            try {
                BaselineFlow::generate($baselineFile, $errors);
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
}
