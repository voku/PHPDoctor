<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

/**
 * @internal
 */
final class CheckFunctions
{
    /**
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param bool                                                 $skipDeprecatedFunctions
     * @param bool                                                 $skipFunctionsWithLeadingUnderscore
     * @param bool                                                 $skipAmbiguousTypesAsError
     * @param bool                                                 $skipParseErrorsAsError
     * @param string[][]                                           $error
     *
     * @return string[][]
     */
    public static function checkFunctions(
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        bool $skipDeprecatedFunctions,
        bool $skipFunctionsWithLeadingUnderscore,
        bool $skipAmbiguousTypesAsError,
        bool $skipParseErrorsAsError,
        array $error
    ): array {
        foreach ($phpInfo->getFunctionsInfo(
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore
        ) as $functionName => $functionInfo) {
            $error = self::checkParameter(
                $functionInfo,
                $skipAmbiguousTypesAsError,
                $functionName,
                $error
            );

            // reset
            $typeFound = false;
            foreach ($functionInfo['returnTypes'] as $key => $type) {
                if ($key === 'typeFromPhpDocMaybeWithComment') {
                    continue;
                }

                if (
                    $type
                    &&
                    ($skipAmbiguousTypesAsError || ($type !== 'mixed' && $type !== 'array'))
                ) {
                    $typeFound = true;
                }
            }
            if ($typeFound) {
                if ($functionInfo['returnTypes']['typeFromPhpDocSimple'] && $functionInfo['returnTypes']['type']) {
                    /** @noinspection ArgumentEqualsDefaultValueInspection */
                    $error = CheckPhpDocType::checkPhpDocType(
                        $functionInfo['returnTypes'],
                        $functionInfo,
                        $functionName . '()',
                        $error,
                        null,
                        null
                    );
                }
            } else {
                $error[$functionInfo['file'] ?? ''][] = '[' . ($functionInfo['line'] ?? '?') . ']: missing return type for ' . $functionName . '()';
            }

            if (!$skipParseErrorsAsError && $functionInfo['error']) {
                $error[$functionInfo['file'] ?? ''][] = '[' . ($functionInfo['line'] ?? '?') . ']: ' . $functionInfo['error'];
            }
        }

        return $error;
    }

    /**
     * @param array  $functionInfo
     * @param bool   $skipAmbiguousTypesAsError
     * @param string $functionName
     * @param array  $error
     *
     * @psalm-param array{fullDescription: string, line: null|int, file: null|string, error: string, is_deprecated: bool, is_meta: bool, is_internal: bool, is_removed: bool, paramsTypes: array<string, array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: null|string, typeFromDefaultValue: null|string}>, returnTypes: array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: null|string} $functionInfo
     *
     * @return string[][]
     */
    private static function checkParameter(
        array $functionInfo,
        bool $skipAmbiguousTypesAsError,
        string $functionName,
        array $error
    ): array {
        foreach ($functionInfo['paramsTypes'] as $paramName => $paramTypes) {
            // reset
            $typeFound = false;
            foreach ($paramTypes as $key => $type) {
                if ($key === 'typeFromPhpDocMaybeWithComment' || $key === 'typeFromDefaultValue') {
                    continue;
                }

                if (
                    $type
                    &&
                    ($skipAmbiguousTypesAsError || ($type !== 'mixed' && $type !== 'array'))
                ) {
                    $typeFound = true;
                }
            }
            if ($typeFound) {
                if ($paramTypes['typeFromPhpDocSimple'] && $paramTypes['type']) {
                    $error = CheckPhpDocType::checkPhpDocType(
                        $paramTypes,
                        $functionInfo,
                        $functionName . '()',
                        $error,
                        null,
                        $paramName
                    );
                }
            } else {
                $error[$functionInfo['file'] ?? ''][] = '[' . ($functionInfo['line'] ?? '?') . ']: missing parameter type for ' . $functionName . '() | parameter:' . $paramName;
            }
        }

        return $error;
    }
}
