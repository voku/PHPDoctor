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

final class PhpDoctorCommand extends Command
{
    public const COMMAND_NAME = 'analyse';

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

        $formatter = $output->getFormatter();
        $formatter->setStyle('file', new OutputFormatterStyle('default', null, ['bold']));
        $formatter->setStyle('error', new OutputFormatterStyle('red', null, []));

        $banner = \sprintf('List of errors in : %s', \implode(' | ', $pathArray));
        $output->writeln(\str_repeat('=', \strlen($banner)));
        $output->writeln($banner);
        $output->writeln(\str_repeat('=', \strlen($banner)));
        $output->writeln('');

        $errors = PhpCodeChecker::checkPhpFiles(
            $pathArray,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError,
            $this->autoloaderProjectPaths,
            [$pathExcludeRegex]
        );

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
        $output->writeln('-------------------------------');

        return $errorCount > 0 ? 1 : 0;
    }
}
