<?php

declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

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
        return self::checkPhpFiles(
            $code,
            $access,
            $skipAmbiguousTypesAsError,
            $skipDeprecatedMethods,
            $skipFunctionsWithLeadingUnderscore,
            $skipParseErrorsAsError
        );
    }

    /**
     * @param string   $path
     * @param bool     $skipAmbiguousTypesAsError
     * @param string[] $access
     * @param bool     $skipDeprecatedFunctions
     * @param bool     $skipFunctionsWithLeadingUnderscore
     * @param bool     $skipParseErrorsAsError
     * @param string[] $autoloaderProjectPaths
     * @param string[] $pathExcludeRegex
     *
     * @return string[][]
     */
    public static function checkPhpFiles(
        string $path,
        array $access = ['public', 'protected', 'private'],
        bool $skipAmbiguousTypesAsError = false,
        bool $skipDeprecatedFunctions = false,
        bool $skipFunctionsWithLeadingUnderscore = false,
        bool $skipParseErrorsAsError = true,
        array $autoloaderProjectPaths = [],
        array $pathExcludeRegex = []
    ): array {
        // init
        $errors = [];

        $phpInfo = PhpCodeParser::getPhpFiles(
            $path,
            $autoloaderProjectPaths,
            $pathExcludeRegex
        );

        if (!$skipParseErrorsAsError) {
            $errors[''] = $phpInfo->getParseErrors();
        }

        $errors = CheckFunctions::checkFunctions(
            $phpInfo,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipAmbiguousTypesAsError,
            $skipParseErrorsAsError,
            $errors
        );

        return CheckClasses::checkClasses(
            $phpInfo,
            $access,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipAmbiguousTypesAsError,
            $skipParseErrorsAsError,
            $errors
        );
    }
}
