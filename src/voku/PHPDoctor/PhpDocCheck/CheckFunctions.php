<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;

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
        $result = self::checkFunctionsWithDiagnostics(
            $phpInfo,
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore,
            $skipAmbiguousTypesAsError,
            $skipParseErrorsAsError,
            $error,
            DiagnosticCollection::empty()
        );

        foreach ($result['diagnostics']->all() as $diagnostic) {
            $result['errors'][$diagnostic->file()][] = DiagnosticToLegacyMessageMapper::map($diagnostic);
        }

        /** @var array<string, array<int, string>> $resultErrors */
        $resultErrors = $result['errors'];

        foreach ($resultErrors as &$errorsInner) {
            \natsort($errorsInner);
            $errorsInner = \array_values($errorsInner);
        }

        return $resultErrors;
    }

    /**
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param bool                                                 $skipDeprecatedFunctions
     * @param bool                                                 $skipFunctionsWithLeadingUnderscore
     * @param bool                                                 $skipAmbiguousTypesAsError
     * @param bool                                                 $skipParseErrorsAsError
     * @param string[][]                                           $error
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function checkFunctionsWithDiagnostics(
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        bool $skipDeprecatedFunctions,
        bool $skipFunctionsWithLeadingUnderscore,
        bool $skipAmbiguousTypesAsError,
        bool $skipParseErrorsAsError,
        array $error,
        DiagnosticCollection $diagnostics
    ): array {
        $functions = $phpInfo->getFunctions();

        foreach ($phpInfo->getFunctionsInfo(
            $skipDeprecatedFunctions,
            $skipFunctionsWithLeadingUnderscore
        ) as $functionName => $functionInfo) {
            $function = $functions[$functionName] ?? null;
            if ($function instanceof \voku\SimplePhpParser\Model\PHPFunction) {
                $diagnostics = self::checkDeprecatedAttributeOnFunction(
                    $function,
                    $diagnostics
                );
            }

            if (!$skipParseErrorsAsError && $functionInfo['error']) {
                $error[$functionInfo['file'] ?? ''][] = '[' . ($functionInfo['line'] ?? '?') . ']: ' . str_replace("\n", ' ', $functionInfo['error']);
            }

            $error = self::checkParameter(
                $functionInfo,
                $skipAmbiguousTypesAsError,
                $functionName,
                $error
            );

            if (
                $functionInfo['returnPhpDocRaw']
                &&
                \strpos($functionInfo['returnPhpDocRaw'], '<phpdoctor-ignore-this-line/>') !== false
            ) {
                continue;
            }

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
                $displayName = $functionName . '()';
                $diagnostics = $diagnostics->with(
                    new Diagnostic(
                        DiagnosticId::MISSING_NATIVE_RETURN_TYPE,
                        $functionInfo['file'] ?? '',
                        $functionInfo['line'] ?? null,
                        [
                            'display_name' => $displayName,
                            'function_or_method_name' => $functionName,
                            'kind' => 'function',
                            'symbol' => $displayName,
                        ]
                    )
                );
            }
        }

        /** @var array<string, array<int, string>> $error */
        return [
            'errors' => $error,
            'diagnostics' => $diagnostics,
        ];
    }

    private static function checkDeprecatedAttributeOnFunction(
        \voku\SimplePhpParser\Model\PHPFunction $function,
        DiagnosticCollection $diagnostics
    ): DiagnosticCollection {
        if (
            !AttributeHelper::hasAttributeNamed($function->attributes, 'Deprecated')
            ||
            $function->hasDeprecatedTag
        ) {
            return $diagnostics;
        }

        return $diagnostics->with(
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                $function->file ?? '',
                $function->line,
                ['display_name' => $function->name . '()']
            )
        );
    }

    /**
     * @param array      $functionInfo
     * @param bool       $skipAmbiguousTypesAsError
     * @param string     $functionName
     * @param string[][] $error
     *
     * @return string[][]
     *
     * @phpstan-param array{
     *     fullDescription: string,
     *     line: null|int,
     *     file: null|string,
     *     error: string,
     *     is_deprecated: bool,
     *     is_meta: bool,
     *     is_internal: bool,
     *     is_removed: bool,
     *     paramsTypes: array<string, array{
     *         type?: null|string,
     *         typeFromPhpDoc?: null|string,
     *         typeFromPhpDocExtended?: null|string,
     *         typeFromPhpDocSimple?: null|string,
     *         typeFromPhpDocMaybeWithComment?: null|string,
     *         typeFromDefaultValue?: null|string
     *     }>,
     *     returnTypes: array{
     *         type: null|string,
     *         typeFromPhpDoc: null|string,
     *         typeFromPhpDocExtended: null|string,
     *         typeFromPhpDocSimple: null|string,
     *         typeFromPhpDocMaybeWithComment: null|string
     *     },
     *     paramsPhpDocRaw: array<string, null|string>,
     *     returnPhpDocRaw: null|string
     *  } $functionInfo
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

            if (
                isset($functionInfo['paramsPhpDocRaw'][$paramName])
                &&
                \strpos($functionInfo['paramsPhpDocRaw'][$paramName], '<phpdoctor-ignore-this-line/>') !== false
            ) {
                continue;
            }

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
                if (($paramTypes['typeFromPhpDocSimple'] ?? null) && ($paramTypes['type'] ?? null)) {
                    $paramTypesNormalized = $paramTypes + [
                        'type' => null,
                        'typeFromPhpDoc' => null,
                        'typeFromPhpDocExtended' => null,
                        'typeFromPhpDocSimple' => null,
                        'typeFromPhpDocMaybeWithComment' => null,
                        'typeFromDefaultValue' => null,
                    ];

                    $error = CheckPhpDocType::checkPhpDocType(
                        $paramTypesNormalized,
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
