<?php

declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
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
        return self::checkFromStringWithDiagnostics(
            $code,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedMethods,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError
        )['errors'];
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
        return self::checkPhpFilesWithDiagnostics(
            $path,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError,
            $autoloaderProjectPaths,
            $pathExcludeRegex,
            $fileExtensions
        )['errors'];
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
                $errors[''] = $phpInfo->getParseErrors();
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

            $errors = CheckClasses::checkClasses(
                $phpInfo,
                $access,
                $skipDeprecatedFunctions,
                $skipFunctionsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $errors
            );
        }

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
}
