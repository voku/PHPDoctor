<?php

declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;
use voku\SimplePhpParser\Parsers\PhpCodeParser;

final class PhpCodeChecker
{
    /**
     * @param string   $code
     * @param string[] $access
     * @param bool     $skipAmbiguousTypesAsError
     * @param bool     $skipDeprecatedMethods
     * @param bool     $skipFunctionsWithLeadingUnderscore
     * @param bool     $skipParseErrorsAsError
     *
     * @return string[][]
     */
    public static function checkFromString(
        string $code,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedMethods = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true
    ): array {
        return self::analyseString(
            $code,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedMethods,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError
        )->toLegacyErrors();
    }

    /**
     * @param string|string[] $path
     * @param bool            $skipAmbiguousTypesAsError
     * @param string[]        $access
     * @param bool            $skipDeprecatedFunctions
     * @param bool            $skipFunctionsWithLeadingUnderscore
     * @param bool            $skipParseErrorsAsError
     * @param string[]        $autoloaderProjectPaths
     * @param string[]        $pathExcludeRegex
     * @param string[]        $fileExtensions
     *
     * @return string[][]
     */
    public static function checkPhpFiles(
        $path,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedFunctions = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true,
        array $autoloaderProjectPaths = [],
        array $pathExcludeRegex = [],
        array $fileExtensions = ['.php']
    ): array {
        return self::analyseFiles(
            $path,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError,
            $autoloaderProjectPaths,
            $pathExcludeRegex,
            $fileExtensions
        )->toLegacyErrors();
    }

    /**
     * @param string   $code
     * @param string[] $access
     * @param bool     $skipAmbiguousTypesAsError
     * @param bool     $skipDeprecatedMethods
     * @param bool     $skipFunctionsWithLeadingUnderscore
     * @param bool     $skipParseErrorsAsError
     */
    public static function analyseString(
        string $code,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedMethods = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true
    ): AnalysisResult {
        return self::analyseFiles(
            $code,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedMethods,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError
        );
    }

    /**
     * @param string|string[] $path
     * @param bool            $skipAmbiguousTypesAsError
     * @param string[]        $access
     * @param bool            $skipDeprecatedFunctions
     * @param bool            $skipFunctionsWithLeadingUnderscore
     * @param bool            $skipParseErrorsAsError
     * @param string[]        $autoloaderProjectPaths
     * @param string[]        $pathExcludeRegex
     * @param string[]        $fileExtensions
     */
    public static function analyseFiles(
        string|array $path,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedFunctions = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true,
        array $autoloaderProjectPaths = [],
        array $pathExcludeRegex = [],
        array $fileExtensions = ['.php']
    ): AnalysisResult {
        $analysisResult = self::checkPhpFilesWithDiagnostics(
            $path,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError,
            $autoloaderProjectPaths,
            $pathExcludeRegex,
            $fileExtensions
        );

        return new AnalysisResult($analysisResult['diagnostics'], $analysisResult['errors']);
    }

    /**
     * @param string   $code
     * @param string[] $access
     * @param bool     $skipAmbiguousTypesAsError
     * @param bool     $skipDeprecatedMethods
     * @param bool     $skipFunctionsWithLeadingUnderscore
     * @param bool     $skipParseErrorsAsError
     *
     * @return array{
     *     errors: array<string, list<string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function checkFromStringWithDiagnostics(
        string $code,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedMethods = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true
    ): array {
        return self::checkPhpFilesWithDiagnostics(
            $code,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedMethods,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError
        );
    }

    /**
     * @param string|string[] $path
     * @param bool            $skipAmbiguousTypesAsError
     * @param string[]        $access
     * @param bool            $skipDeprecatedFunctions
     * @param bool            $skipFunctionsWithLeadingUnderscore
     * @param bool            $skipParseErrorsAsError
     * @param string[]        $autoloaderProjectPaths
     * @param string[]        $pathExcludeRegex
     * @param string[]        $fileExtensions
     *
     * @return array{
     *     errors: array<string, list<string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function checkPhpFilesWithDiagnostics(
        $path,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedFunctions = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true,
        array $autoloaderProjectPaths = [],
        array $pathExcludeRegex = [],
        array $fileExtensions = ['.php']
    ): array {
        // init
        /** @var array<string, array<int, string>> $errors */
        $errors = [];
        $diagnostics = DiagnosticCollection::empty();
        $parseErrors = [];

        if (!\is_array($path)) {
            $path = [$path];
        }

        foreach ($path as $pathItem) {
            $phpInfo = PhpCodeParser::getPhpFiles(
                $pathItem,
                $autoloaderProjectPaths,
                $pathExcludeRegex,
                $fileExtensions
            );

            if (!$skipParseErrorsAsError) {
                $parseErrors = \array_values($phpInfo->getParseErrors());
            }

            $functionCheckResult = CheckFunctions::checkFunctionsWithDiagnostics(
                $phpInfo,
                $skipDeprecatedFunctions,
                $skipFunctionsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $errors,
                $diagnostics
            );
            $errors = $functionCheckResult['errors'];
            $diagnostics = $functionCheckResult['diagnostics'];

            $classCheckResult = CheckClasses::checkClassesWithDiagnostics(
                $phpInfo,
                $access,
                $skipDeprecatedFunctions,
                $skipFunctionsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $errors,
                $diagnostics
            );
            $errors = $classCheckResult['errors'];
            $diagnostics = $classCheckResult['diagnostics'];
        }

        if (!$skipParseErrorsAsError) {
            $diagnostics = self::parseErrorDiagnosticsFromMessages($parseErrors, $diagnostics);
        }

        // Keep the legacy string errors for unchanged text output and external callers
        // while typed diagnostics feed profile and baseline generation.
        foreach ($diagnostics->all() as $diagnostic) {
            $errors[$diagnostic->file()][] = DiagnosticToLegacyMessageMapper::map($diagnostic);
        }

        foreach ($errors as &$errorsInner) {
            \natsort($errorsInner);
            $errorsInner = \array_values($errorsInner);
        }
        /** @var array<string, list<string>> $errors */

        return [
            'errors' => $errors,
            'diagnostics' => $diagnostics,
        ];
    }

    /**
     * @param list<string> $parseErrors
     */
    private static function parseErrorDiagnosticsFromMessages(
        array $parseErrors,
        DiagnosticCollection $diagnostics
    ): DiagnosticCollection {
        foreach ($parseErrors as $parseError) {
            $line = null;
            if (\preg_match('/^\[(\d+)\]: /', $parseError, $matches) === 1) {
                $line = (int) $matches[1];
            }

            $diagnostics = $diagnostics->with(
                new Diagnostic(
                    DiagnosticId::PARSER_SYNTAX_ERROR,
                    '',
                    $line,
                    ['legacy_message' => $parseError]
                )
            );
        }

        return $diagnostics;
    }
}
