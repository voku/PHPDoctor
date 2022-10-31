<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

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
}
