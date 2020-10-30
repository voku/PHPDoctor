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
     * @param string|string[] $path
     * @param bool            $skipAmbiguousTypesAsError
     * @param string[]        $access
     * @param bool            $skipDeprecatedFunctions
     * @param bool            $skipFunctionsWithLeadingUnderscore
     * @param bool            $skipParseErrorsAsError
     * @param string[]        $autoloaderProjectPaths
     * @param string[]        $pathExcludeRegex
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
        array $pathExcludeRegex = []
    ): array {
        // init
        $errors = [];

        if (!\is_array($path)) {
            $path = [$path];
        }

        foreach ($path as $pathItem) {
            $phpInfo = PhpCodeParser::getPhpFiles(
                $pathItem,
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

        foreach ($errors as $file => &$errorsInner) {
            \natsort($errorsInner);
            $errorsInner = \array_values($errorsInner);
        }

        return $errors;
    }
}
