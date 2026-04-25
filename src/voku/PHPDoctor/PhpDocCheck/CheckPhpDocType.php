<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\SimplePhpParser\Parsers\Helper\Utils;

/**
 * @internal
 */
final class CheckPhpDocType
{
    /**
     * @param array                                                                                                                                                                                                                                                                                                                                                                                          $types
     * @param array                                                                                                                                                                                                                                                                                                                                                                                          $fileInfo
     * @param string[][]                                                                                                                                                                                                                                                                                                                                                                                     $errors
     * @param string                                                                                                                                                                                                                                                                                                                                                                                         $name
     * @param string|null                                                                                                                                                                                                                                                                                                                                                                                    $className
     * @param string|null                                                                                                                                                                                                                                                                                                                                                                                    $paramName
     * @param string|null                                                                                                                                                                                                                                                                                                                                                                                    $propertyName
     *
     * @psalm-param array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: string|null}|array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: string|null, typeFromDefaultValue: null|string} $types
     * @psalm-param array{line: null|int, file: null|string}                                                                                                                                                                                                                                                                                                                                                 $fileInfo
     *
     * @return string[][]
     */
    public static function checkPhpDocType(
        array  $types,
        array  $fileInfo,
        string $name,
        array  $errors,
        string $className = null,
        string $paramName = null,
        string $propertyName = null
    ): array {
        // init
        $typeFromPhpWithoutNullArray = [];
        $typeFromPhpDocInput = $types['typeFromPhpDocSimple'];
        $typeFromPhpInput = $types['type'];

        // native "mixed" always wins
        if (
            $typeFromPhpInput === 'null|mixed'
            ||
            $typeFromPhpDocInput === 'mixed'
        ) {
            return $errors;
        }

        $typeFromPhpDocInputArray = \explode('|', $typeFromPhpDocInput ?? '');

        if (
            isset($types['typeFromDefaultValue'])
            &&
            $types['typeFromDefaultValue'] === 'null'
        ) {
            if ($typeFromPhpInput) {
                $typeFromPhpInput .= '|null';
            } else {
                $typeFromPhpInput = 'null';
            }
        }

        $removeEmptyStringFunc = static function (?string $tmp): bool {
            return $tmp !== '';
        };
        $typeFromPhpDoc = \array_unique(
            \array_filter(
                $typeFromPhpDocInputArray,
                $removeEmptyStringFunc
            )
        );
        foreach ($typeFromPhpDoc as $keyTmp => $typeFromPhpDocSingle) {
            if (
                $typeFromPhpDocSingle === '$this'
                ||
                $typeFromPhpDocSingle === 'static'
                ||
                $typeFromPhpDocSingle === 'self'
            ) {
                $typeFromPhpDoc[$keyTmp] = $className;
            }

            if (\is_string($typeFromPhpDoc[$keyTmp])) {
                $typeFromPhpDoc[$keyTmp] = \ltrim($typeFromPhpDoc[$keyTmp], '\\');
            }
        }
        $typeFromPhp = \array_unique(
            \array_filter(
                \explode('|', $typeFromPhpInput ?? ''),
                $removeEmptyStringFunc
            )
        );

        foreach ($typeFromPhp as $keyTmp => $typeFromPhpSingle) {
            if (
                $typeFromPhpSingle === '$this'
                ||
                $typeFromPhpSingle === 'static'
                ||
                $typeFromPhpSingle === 'self'
            ) {
                $typeFromPhp[$keyTmp] = $className;
            }

            if (\is_string($typeFromPhp[$keyTmp])) {
                $typeFromPhp[$keyTmp] = \ltrim($typeFromPhp[$keyTmp], '\\');
            }

            if ($typeFromPhpSingle && \strtolower($typeFromPhpSingle) !== 'null') {
                $typeFromPhpWithoutNullArray[$keyTmp] = $typeFromPhp[$keyTmp];
            }
        }

        if (
            \count($typeFromPhpDoc) > 0
            &&
            \count($typeFromPhp) > 0
        ) {
            foreach ($typeFromPhp as $typeFromPhpSingle) {
                // reset
                $checked = null;

                if (
                    $typeFromPhpSingle
                    &&
                    $typeFromPhpDocInput
                    &&
                    !\in_array($typeFromPhpSingle, $typeFromPhpDoc, true)
                    &&
                    (
                        $typeFromPhpSingle === 'array' && \strpos($typeFromPhpDocInput, '[]') === false
                        ||
                        $typeFromPhpSingle !== 'array'
                    )
                ) {
                    $checked = false;

                    if (
                        $typeFromPhpSingle === 'bool'
                        &&
                        (
                            \in_array('true', $typeFromPhpDocInputArray, true)
                            ||
                            \in_array('false', $typeFromPhpDocInputArray, true)
                        )
                    ) {
                        $checked = true;
                    }

                    if (
                        $typeFromPhpSingle === 'string'
                        &&
                        \in_array('class-string', $typeFromPhpDocInputArray, true)
                    ) {
                        $checked = true;
                    }

                    if (
                        $checked === false
                        &&
                        (
                            \class_exists($typeFromPhpSingle, true)
                            ||
                            \interface_exists($typeFromPhpSingle, true)
                        )
                    ) {
                        foreach ($typeFromPhpDoc as $typeFromPhpDocTmp) {
                            // prevent false-positive results if the namespace is only imported party etc.
                            if (
                                $typeFromPhpDocTmp
                                &&
                                (
                                    $typeFromPhpDocTmp === $typeFromPhpSingle
                                    ||
                                    \strpos($typeFromPhpSingle, $typeFromPhpDocTmp) !== false
                                )
                            ) {
                                $checked = true;

                                break;
                            }

                            if (
                                $typeFromPhpDocTmp
                                &&
                                (
                                    \class_exists($typeFromPhpDocTmp, true)
                                    ||
                                    \interface_exists($typeFromPhpDocTmp, true)
                                )
                                &&
                                (
                                    /** @phpstan-ignore-next-line */
                                    ($typeFromPhpDocReflectionClass = Utils::createClassReflectionInstance($typeFromPhpDocTmp))
                                    &&
                                    (
                                        $typeFromPhpDocReflectionClass->isSubclassOf($typeFromPhpSingle)
                                        ||
                                        $typeFromPhpDocReflectionClass->implementsInterface($typeFromPhpSingle)
                                    )
                                )
                            ) {
                                $checked = true;

                                break;
                            }
                        }
                    }

                    // native "mixed" always wins
                    if ($typeFromPhpSingle === 'mixed') {
                        $checked = true;
                    }

                    if (!$checked) {
                        if ($propertyName) {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing property type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name . ' | property:' . $propertyName;
                        } elseif ($paramName) {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing parameter type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name . ' | parameter:' . $paramName;
                        } else {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing return type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name;
                        }
                    }
                }
            }

            foreach ($typeFromPhpDoc as $typeFromPhpDocSingle) {
                if (!\in_array($typeFromPhpDocSingle, $typeFromPhp, true)) {
                    // reset
                    $checked = null;

                    if (
                        \in_array('bool', $typeFromPhpWithoutNullArray, true)
                        &&
                        (
                            $typeFromPhpDocSingle === 'true'
                            ||
                            $typeFromPhpDocSingle === 'false'
                        )
                    ) {
                        $checked = true;
                    }

                    if (
                        $typeFromPhpDocSingle
                        &&
                        \in_array('string', $typeFromPhpWithoutNullArray, true)
                        &&
                        \strpos($typeFromPhpDocSingle, 'class-string') === 0
                    ) {
                        $checked = true;
                    }

                    if (
                        $typeFromPhpDocSingle
                        &&
                        $typeFromPhpWithoutNullArray !== []
                        &&
                        (
                            \in_array('array', $typeFromPhpWithoutNullArray, true)
                            ||
                            \in_array('Generator', $typeFromPhpWithoutNullArray, true)
                        )
                        &&
                        \strpos($typeFromPhpDocSingle, '[]') !== false
                    ) {
                        $checked = true;
                    }

                    if (
                        !$checked
                        &&
                        $typeFromPhpWithoutNullArray !== []
                    ) {
                        $checked = false;

                        // prevent false-positive results if the namespace is only imported party etc.
                        if (
                            $typeFromPhpDocSingle
                            &&
                            (
                                $typeFromPhpDocSingle === implode('|', $typeFromPhpWithoutNullArray)
                                ||
                                \in_array($typeFromPhpDocSingle, $typeFromPhpWithoutNullArray, true)
                            )
                        ) {
                            $checked = true;
                        }

                        foreach ($typeFromPhpWithoutNullArray as $typeFromPhpWithoutNullSingle) {
                            if (!\is_string($typeFromPhpWithoutNullSingle)) {
                                continue;
                            }

                            if (
                                $checked === false
                                &&
                                $typeFromPhpDocSingle
                                &&
                                (
                                    \class_exists($typeFromPhpWithoutNullSingle, true)
                                    ||
                                    \interface_exists($typeFromPhpWithoutNullSingle, true)
                                )
                                &&
                                (
                                    \class_exists($typeFromPhpDocSingle, true)
                                    ||
                                    \interface_exists($typeFromPhpDocSingle, true)
                                )
                            ) {
                                $typeFromPhpDocReflectionClass = Utils::createClassReflectionInstance($typeFromPhpDocSingle);
                                if (
                                    $typeFromPhpDocReflectionClass->isSubclassOf($typeFromPhpWithoutNullSingle)
                                    ||
                                    $typeFromPhpDocReflectionClass->implementsInterface($typeFromPhpWithoutNullSingle)
                                ) {
                                    $checked = true;
                                }
                            }
                        }
                    }

                    // native "mixed" always wins
                    if (\in_array('mixed', $typeFromPhpWithoutNullArray, true)) {
                        $checked = true;
                    }

                    if (!$checked) {
                        if ($propertyName) {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong property type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name . '  | property:' . $propertyName;
                        } elseif ($paramName) {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong parameter type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name . '  | parameter:' . $paramName;
                        } else {
                            $errors[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong return type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @internal
     *
     * @param array{
     *     type: null|string,
     *     typeFromPhpDoc: null|string,
     *     typeFromPhpDocExtended: null|string,
     *     typeFromPhpDocSimple: null|string,
     *     typeFromPhpDocMaybeWithComment: null|string,
     *     typeFromDefaultValue?: null|string
     * } $parameterTypes
     */
    public static function ambiguousParameterDiagnostic(
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        string $parameterName,
        array $parameterTypes,
        string $kind,
        int $parameterPosition,
        ?string $declaringClass = null
    ): ?Diagnostic {
        $phpdocType = self::ambiguousPhpDocType($parameterTypes);
        if ($phpdocType === null) {
            return null;
        }

        $diagnosticEvidence = [];
        if ($declaringClass !== null) {
            $diagnosticEvidence['declaring_class'] = $declaringClass;
        }

        $nativeType = $parameterTypes['type'] ?? null;
        if (\is_string($nativeType) && $nativeType !== '') {
            $diagnosticEvidence['native_type'] = $nativeType;
        }

        $diagnosticEvidence += [
            'display_name' => $displayName,
            'function_or_method_name' => $functionOrMethodName,
            'parameter_name' => $parameterName,
            'kind' => $kind,
            'parameter_position' => $parameterPosition,
            'phpdoc_type' => $phpdocType,
            'symbol' => $displayName . ' | parameter:' . $parameterName,
        ];

        return new Diagnostic(
            DiagnosticId::AMBIGUOUS_PHPDOC_PARAMETER_TYPE,
            $file,
            $line,
            $diagnosticEvidence
        );
    }

    /**
     * @param array{
     *     type: null|string,
     *     typeFromPhpDoc: null|string,
     *     typeFromPhpDocExtended: null|string,
     *     typeFromPhpDocSimple: null|string,
     *     typeFromPhpDocMaybeWithComment: null|string
     * } $returnTypes
     */
    public static function ambiguousReturnDiagnostic(
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        array $returnTypes,
        string $kind,
        ?string $declaringClass = null
    ): ?Diagnostic {
        $phpdocType = self::ambiguousPhpDocType($returnTypes);
        if ($phpdocType === null) {
            return null;
        }

        $diagnosticEvidence = [];
        if ($declaringClass !== null) {
            $diagnosticEvidence['declaring_class'] = $declaringClass;
        }

        $nativeType = $returnTypes['type'] ?? null;
        if (\is_string($nativeType) && $nativeType !== '') {
            $diagnosticEvidence['native_type'] = $nativeType;
        }

        $diagnosticEvidence += [
            'display_name' => $displayName,
            'function_or_method_name' => $functionOrMethodName,
            'kind' => $kind,
            'phpdoc_type' => $phpdocType,
            'symbol' => $displayName,
        ];

        return new Diagnostic(
            DiagnosticId::AMBIGUOUS_PHPDOC_RETURN_TYPE,
            $file,
            $line,
            $diagnosticEvidence
        );
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array<int, string>|null
     */
    private static function legacyMessagesForFile(array $errors, string $file): ?array
    {
        /** @var array<int, string>|null $messages */
        $messages = $errors[$file] ?? null;

        return \is_array($messages) ? $messages : null;
    }

    /**
     * @return array<int, string>|null
     */
    private static function matchLegacyMessage(string $pattern, string $message): ?array
    {
        $matches = [];
        if (\preg_match($pattern, $message, $matches) !== 1) {
            return null;
        }

        /** @var array<int, string> $matches */
        return $matches;
    }

    /**
     * @param array{
     *     type: null|string,
     *     typeFromPhpDoc: null|string,
     *     typeFromPhpDocExtended: null|string,
     *     typeFromPhpDocSimple: null|string,
     *     typeFromPhpDocMaybeWithComment: null|string,
     *     typeFromDefaultValue?: null|string
     * } $parameterTypes
     */
    private static function ambiguousPhpDocType(array $parameterTypes): ?string
    {
        foreach (['typeFromPhpDocExtended', 'typeFromPhpDoc', 'typeFromPhpDocSimple'] as $key) {
            $phpdocType = $parameterTypes[$key] ?? null;
            if (!\is_string($phpdocType) || $phpdocType === '') {
                continue;
            }

            if ($phpdocType === 'mixed' || $phpdocType === 'array') {
                return $phpdocType;
            }

            return null;
        }

        return null;
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @param array<int, string>                $remainingMessages
     *
     * @return array<string, array<int, string>>
     */
    private static function withRemainingLegacyMessages(array $errors, string $file, array $remainingMessages): array
    {
        if ($remainingMessages === []) {
            unset($errors[$file]);
        } else {
            $errors[$file] = $remainingMessages;
        }

        return $errors;
    }

    private static function appendDiagnostic(
        DiagnosticCollection $diagnostics,
        Diagnostic $diagnostic
    ): DiagnosticCollection {
        return $diagnostics->with($diagnostic);
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    private static function migrationResult(array $errors, DiagnosticCollection $diagnostics): array
    {
        return [
            'errors' => $errors,
            'diagnostics' => $diagnostics,
        ];
    }

    /**
     * @param array<string, array<int, string>>        $errors
     * @param callable(array<int, string>): Diagnostic $diagnosticFactory
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    private static function migrateLegacyErrorsToDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        string $file,
        string $pattern,
        callable $diagnosticFactory
    ): array {
        $messages = self::legacyMessagesForFile($errors, $file);
        if ($messages === null) {
            return self::migrationResult($errors, $diagnostics);
        }

        $remainingMessages = [];
        foreach ($messages as $message) {
            $matches = self::matchLegacyMessage($pattern, $message);
            if ($matches === null) {
                $remainingMessages[] = $message;

                continue;
            }

            $diagnostics = self::appendDiagnostic($diagnostics, $diagnosticFactory($matches));
        }

        $errors = self::withRemainingLegacyMessages($errors, $file, $remainingMessages);

        return self::migrationResult($errors, $diagnostics);
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function migrateMissingParameterErrorsToDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        string $parameterName,
        string $kind,
        int $parameterPosition,
        ?string $declaringClass = null
    ): array {
        $pattern = '/^\[(\d+|\?)\]: missing parameter type "(.+)" in phpdoc from '
            . \preg_quote($displayName, '/')
            . ' \| parameter:'
            . \preg_quote($parameterName, '/')
            . '$/';

        return self::migrateLegacyErrorsToDiagnostics(
            $errors,
            $diagnostics,
            $file,
            $pattern,
            static function (array $matches) use (
                $declaringClass,
                $displayName,
                $functionOrMethodName,
                $parameterName,
                $kind,
                $parameterPosition,
                $file,
                $line
            ): Diagnostic {
                $diagnosticEvidence = [];
                if ($declaringClass !== null) {
                    $diagnosticEvidence['declaring_class'] = $declaringClass;
                }

                $diagnosticEvidence += [
                    'display_name' => $displayName,
                    'function_or_method_name' => $functionOrMethodName,
                    'parameter_name' => $parameterName,
                    'kind' => $kind,
                    'missing_type' => $matches[2],
                    'parameter_position' => $parameterPosition,
                    'symbol' => $displayName . ' | parameter:' . $parameterName,
                ];

                return new Diagnostic(
                    DiagnosticId::MISSING_PHPDOC_PARAMETER_TYPE,
                    $file,
                    $matches[1] !== '?' ? (int) $matches[1] : $line,
                    $diagnosticEvidence
                );
            }
        );
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function migrateWrongParameterErrorsToDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        string $parameterName,
        string $kind,
        int $parameterPosition,
        ?string $declaringClass = null,
        ?string $nativeType = null
    ): array {
        $pattern = '/^\[(\d+|\?)\]: wrong parameter type "([^"]+)" in phpdoc from '
            . \preg_quote($displayName, '/')
            . '  \| parameter:'
            . \preg_quote($parameterName, '/')
            . '$/';

        return self::migrateLegacyErrorsToDiagnostics(
            $errors,
            $diagnostics,
            $file,
            $pattern,
            static function (array $matches) use (
                $declaringClass,
                $nativeType,
                $displayName,
                $functionOrMethodName,
                $parameterName,
                $kind,
                $parameterPosition,
                $file,
                $line
            ): Diagnostic {
                $diagnosticEvidence = [];
                if ($declaringClass !== null) {
                    $diagnosticEvidence['declaring_class'] = $declaringClass;
                }
                if ($nativeType !== null && $nativeType !== '') {
                    $diagnosticEvidence['native_type'] = $nativeType;
                }

                $diagnosticEvidence += [
                    'display_name' => $displayName,
                    'function_or_method_name' => $functionOrMethodName,
                    'parameter_name' => $parameterName,
                    'kind' => $kind,
                    'parameter_position' => $parameterPosition,
                    'phpdoc_type' => $matches[2],
                    'symbol' => $displayName . ' | parameter:' . $parameterName,
                ];

                return new Diagnostic(
                    DiagnosticId::WRONG_PHPDOC_PARAMETER_TYPE,
                    $file,
                    $matches[1] !== '?' ? (int) $matches[1] : $line,
                    $diagnosticEvidence
                );
            }
        );
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function migrateMissingReturnErrorsToDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        string $kind,
        ?string $declaringClass = null
    ): array {
        $pattern = '/^\[(\d+|\?)\]: missing return type "(.+)" in phpdoc from '
            . \preg_quote($displayName, '/')
            . '$/';

        return self::migrateLegacyErrorsToDiagnostics(
            $errors,
            $diagnostics,
            $file,
            $pattern,
            static function (array $matches) use (
                $declaringClass,
                $displayName,
                $functionOrMethodName,
                $kind,
                $file,
                $line
            ): Diagnostic {
                $diagnosticEvidence = [];
                if ($declaringClass !== null) {
                    $diagnosticEvidence['declaring_class'] = $declaringClass;
                }

                $diagnosticEvidence += [
                    'display_name' => $displayName,
                    'function_or_method_name' => $functionOrMethodName,
                    'kind' => $kind,
                    'missing_type' => $matches[2],
                    'symbol' => $displayName,
                ];

                return new Diagnostic(
                    DiagnosticId::MISSING_PHPDOC_RETURN_TYPE,
                    $file,
                    $matches[1] !== '?' ? (int) $matches[1] : $line,
                    $diagnosticEvidence
                );
            }
        );
    }

    /**
     * @param array<string, array<int, string>> $errors
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function migrateWrongReturnErrorsToDiagnostics(
        array $errors,
        DiagnosticCollection $diagnostics,
        string $file,
        ?int $line,
        string $displayName,
        string $functionOrMethodName,
        string $kind,
        ?string $declaringClass = null,
        ?string $nativeType = null
    ): array {
        $pattern = '/^\[(\d+|\?)\]: wrong return type "([^"]+)" in phpdoc from '
            . \preg_quote($displayName, '/')
            . '$/';

        return self::migrateLegacyErrorsToDiagnostics(
            $errors,
            $diagnostics,
            $file,
            $pattern,
            static function (array $matches) use (
                $declaringClass,
                $nativeType,
                $displayName,
                $functionOrMethodName,
                $kind,
                $file,
                $line
            ): Diagnostic {
                $diagnosticEvidence = [];
                if ($declaringClass !== null) {
                    $diagnosticEvidence['declaring_class'] = $declaringClass;
                }
                if ($nativeType !== null && $nativeType !== '') {
                    $diagnosticEvidence['native_type'] = $nativeType;
                }

                $diagnosticEvidence += [
                    'display_name' => $displayName,
                    'function_or_method_name' => $functionOrMethodName,
                    'kind' => $kind,
                    'phpdoc_type' => $matches[2],
                    'symbol' => $displayName,
                ];

                return new Diagnostic(
                    DiagnosticId::WRONG_PHPDOC_RETURN_TYPE,
                    $file,
                    $matches[1] !== '?' ? (int) $matches[1] : $line,
                    $diagnosticEvidence
                );
            }
        );
    }
}
