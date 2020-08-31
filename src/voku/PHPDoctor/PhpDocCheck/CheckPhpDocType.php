<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

/**
 * @internal
 */
final class CheckPhpDocType
{
    /**
     * @param array       $types
     * @param array       $fileInfo
     * @param string[][]  $error
     * @param string      $name
     * @param string|null $className
     * @param string|null $paramName
     * @param string|null $propertyName
     *
     * @psalm-param array{type: string, typeFromPhpDoc: string|null, typeFromPhpDocExtended: string|null, typeFromPhpDocSimple: string, typeFromPhpDocMaybeWithComment => string|null} $types
     * @psalm-param array{fullDescription: string, line: null|int, file: null|string, error: string, is_deprecated: bool, is_meta: bool, is_internal: bool, is_removed: bool, paramsTypes: array<string, array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: null|string, typeFromDefaultValue: null|string}>, returnTypes: array{type: null|string, typeFromPhpDoc: null|string, typeFromPhpDocExtended: null|string, typeFromPhpDocSimple: null|string, typeFromPhpDocMaybeWithComment: null|string} $fileInfo
     *
     * @return string[][]
     */
    public static function checkPhpDocType(
        array $types,
        array $fileInfo,
        string $name,
        array $error,
        string $className = null,
        string $paramName = null,
        string $propertyName = null
    ): array {
        // init
        $typeFromPhpWithoutNull = null;
        $typeFromPhpDocInput = $types['typeFromPhpDocSimple'];
        $typeFromPhpInput = $types['type'];
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
                \explode('|', $typeFromPhpDocInput ?? ''),
                $removeEmptyStringFunc
            )
        );
        /** @noinspection AlterInForeachInspection */
        foreach ($typeFromPhpDoc as $keyTmp => $typeFromPhpDocSingle) {
            /** @noinspection InArrayCanBeUsedInspection */
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
        /** @noinspection AlterInForeachInspection */
        foreach ($typeFromPhp as $keyTmp => $typeFromPhpSingle) {
            /** @noinspection InArrayCanBeUsedInspection */
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
                $typeFromPhpWithoutNull = $typeFromPhp[$keyTmp];
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

                /** @noinspection SuspiciousBinaryOperationInspection */
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

                    /** @noinspection ArgumentEqualsDefaultValueInspection */
                    if (
                    (
                        \class_exists($typeFromPhpSingle, true)
                        ||
                        \interface_exists($typeFromPhpSingle, true)
                    )
                    ) {
                        foreach ($typeFromPhpDoc as $typeFromPhpDocTmp) {
                            /** @noinspection ArgumentEqualsDefaultValueInspection */
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
                                    ($typeFromPhpDocReflectionClass = \Roave\BetterReflection\Reflection\ReflectionClass::createFromName($typeFromPhpDocTmp))
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

                    if (!$checked) {
                        if ($propertyName) {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing property type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name . ' | property:' . $propertyName;
                        } elseif ($paramName) {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing parameter type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name . ' | parameter:' . $paramName;
                        } else {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: missing return type "' . $typeFromPhpSingle . '" in phpdoc from ' . $name;
                        }
                    }
                }
            }

            foreach ($typeFromPhpDoc as $typeFromPhpDocSingle) {
                // reset
                /** @noinspection SuspiciousBinaryOperationInspection */
                /** @noinspection NotOptimalIfConditionsInspection */
                if (
                    (
                        $typeFromPhpDocSingle === 'null'
                        &&
                        !\in_array($typeFromPhpDocSingle, $typeFromPhp, true)
                    )
                    ||
                    (
                        $typeFromPhpDocSingle !== 'null'
                        &&
                        $typeFromPhpWithoutNull
                        &&
                        $typeFromPhpDocSingle !== $typeFromPhpWithoutNull
                    )
                ) {
                    // reset
                    $checked = null;

                    if (
                        $typeFromPhpWithoutNull === 'bool'
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
                        $typeFromPhpWithoutNull
                        &&
                        (
                            $typeFromPhpWithoutNull === 'array'
                            ||
                            \ltrim($typeFromPhpWithoutNull, '\\') === 'Generator'
                        )
                        &&
                        \strpos($typeFromPhpDocSingle, '[]') !== false
                    ) {
                        $checked = true;
                    }

                    if (
                        !$checked
                        &&
                        $typeFromPhpWithoutNull
                    ) {
                        $checked = false;

                        /** @noinspection ArgumentEqualsDefaultValueInspection */
                        if (
                            $typeFromPhpDocSingle
                            &&
                            (
                                \class_exists($typeFromPhpWithoutNull, true)
                                ||
                                \interface_exists($typeFromPhpWithoutNull, true)
                            )
                            &&
                            (
                                \class_exists($typeFromPhpDocSingle, true)
                                ||
                                \interface_exists($typeFromPhpDocSingle, true)
                            )
                        ) {
                            $typeFromPhpDocReflectionClass = \Roave\BetterReflection\Reflection\ReflectionClass::createFromName($typeFromPhpDocSingle);
                            if (
                                $typeFromPhpDocReflectionClass->isSubclassOf($typeFromPhpWithoutNull)
                                ||
                                $typeFromPhpDocReflectionClass->implementsInterface($typeFromPhpWithoutNull)
                            ) {
                                $checked = true;
                            }
                        }
                    }

                    if (!$checked) {
                        if ($propertyName) {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong property type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name . '  | property:' . $propertyName;
                        } elseif ($paramName) {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong parameter type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name . '  | parameter:' . $paramName;
                        } else {
                            $error[$fileInfo['file'] ?? ''][] = '[' . ($fileInfo['line'] ?? '?') . ']: wrong return type "' . ($typeFromPhpDocSingle ?? '?') . '" in phpdoc from ' . $name;
                        }
                    }
                }
            }
        }

        return $error;
    }
}
