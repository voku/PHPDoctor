<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

/**
 * @internal
 */
final class CheckClasses
{
    /**
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param string[]                                             $access
     * @param bool                                                 $skipDeprecatedMethods
     * @param bool                                                 $skipMethodsWithLeadingUnderscore
     * @param bool                                                 $skipAmbiguousTypesAsError
     * @param bool                                                 $skipParseErrorsAsError
     * @param string[][]                                           $error
     *
     * @return string[][]
     */
    public static function checkClasses(
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        array                                                $access,
        bool                                                 $skipDeprecatedMethods,
        bool                                                 $skipMethodsWithLeadingUnderscore,
        bool                                                 $skipAmbiguousTypesAsError,
        bool                                                 $skipParseErrorsAsError,
        array                                                $error
    ): array {
        foreach (array_merge($phpInfo->getTraits(), $phpInfo->getClasses()) as $class) {
            $error = self::checkProperties(
                $class,
                $access,
                $skipMethodsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $error
            );

            $error = self::checkMethods(
                $class,
                $access,
                $skipDeprecatedMethods,
                $skipMethodsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $error
            );
        }

        return $error;
    }

    /**
     * @param \voku\SimplePhpParser\Model\BasePHPClass $class
     * @param string[]                                 $access
     * @param bool                                     $skipDeprecatedMethods
     * @param bool                                     $skipMethodsWithLeadingUnderscore
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param bool                                     $skipParseErrorsAsError
     * @param string[][]                               $error
     *
     * @return string[][]
     */
    private static function checkMethods(
        \voku\SimplePhpParser\Model\BasePHPClass $class,
        array                                    $access,
        bool                                     $skipDeprecatedMethods,
        bool                                     $skipMethodsWithLeadingUnderscore,
        bool                                     $skipAmbiguousTypesAsError,
        bool                                     $skipParseErrorsAsError,
        array                                    $error
    ): array
    {
        foreach ($class->getMethodsInfo(
            $access,
            $skipDeprecatedMethods,
            $skipMethodsWithLeadingUnderscore
        ) as $methodName => $methodInfo) {

            if (!$skipParseErrorsAsError && $methodInfo['error']) {
                $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: ' . str_replace("\n", ' ', $methodInfo['error']);
            }

            $error = self::checkParameter(
                $methodInfo,
                $skipAmbiguousTypesAsError,
                $class,
                $methodName,
                $error
            );

            if (
                $methodInfo['returnPhpDocRaw']
                &&
                \strpos($methodInfo['returnPhpDocRaw'], '<phpdoctor-ignore-this-line/>') !== false
            ) {
                continue;
            }

            /** @noinspection InArrayCanBeUsedInspection */
            if (
                $methodName !== '__construct'
                &&
                $methodName !== '__destruct'
                &&
                $methodName !== '__unset'
                &&
                $methodName !== '__wakeup'
                &&
                $methodName !== '__clone'
            ) {
                // reset
                $typeFound = false;

                foreach ($methodInfo['returnTypes'] as $key => $type) {
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
                    if ($methodInfo['returnTypes']['typeFromPhpDocSimple'] && $methodInfo['returnTypes']['type']) {
                        /** @noinspection ArgumentEqualsDefaultValueInspection */
                        $error = CheckPhpDocType::checkPhpDocType(
                            $methodInfo['returnTypes'],
                            $methodInfo,
                            ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '()',
                            $error,
                            $class->name ?? null,
                            null
                        );
                    }
                } else {
                    $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: missing return type for ' . ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '()';
                }
            }
        }

        return $error;
    }

    /**
     * @param array                                    $methodInfo
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param \voku\SimplePhpParser\Model\BasePHPClass $class
     * @param string                                   $methodName
     * @param string[][]                               $error
     *
     * @return string[][]
     *
     * @phpstan-param array{
     *     fullDescription: string,
     *     line: null|int,
     *     file: null|string,
     *     error: string,
     *     is_deprecated: bool,
     *     is_static: null|bool,
     *     is_meta: bool,
     *     is_internal: bool,
     *     is_removed: bool,
     *     paramsTypes: array<string,
     *         array{
     *           type: null|string,
     *           typeFromPhpDoc: null|string,
     *           typeFromPhpDocExtended: null|string,
     *           typeFromPhpDocSimple: null|string,
     *           typeFromPhpDocMaybeWithComment: null|string,
     *           typeFromDefaultValue: null|string
     *         }
     *     >,
     *     returnTypes: array{
     *         type: null|string,
     *         typeFromPhpDoc: null|string,
     *         typeFromPhpDocExtended: null|string,
     *         typeFromPhpDocSimple: null|string,
     *         typeFromPhpDocMaybeWithComment: null|string
     *     },
     *     paramsPhpDocRaw: array<string, null|string>,
     *     returnPhpDocRaw: null|string
     * } $methodInfo
     */
    private static function checkParameter(
        $methodInfo,
        bool $skipAmbiguousTypesAsError,
        \voku\SimplePhpParser\Model\BasePHPClass $class,
        string $methodName,
        array $error
    ): array
    {
        foreach ($methodInfo['paramsTypes'] as $paramName => $paramTypes) {
            // reset
            $typeFound = false;

            if (
                isset($methodInfo['paramsPhpDocRaw'][$paramName])
                &&
                \strpos($methodInfo['paramsPhpDocRaw'][$paramName], '<phpdoctor-ignore-this-line/>') !== false
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
                if ($paramTypes['typeFromPhpDocSimple'] && $paramTypes['type']) {
                    $error = CheckPhpDocType::checkPhpDocType(
                        $paramTypes,
                        $methodInfo,
                        ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '()',
                        $error,
                        ($class->name ?? null),
                        $paramName
                    );
                }
            } else {
                $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: missing parameter type for ' . ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '() | parameter:' . $paramName;
            }
        }

        return $error;
    }

    /**
     * @param \voku\SimplePhpParser\Model\BasePHPClass $class
     * @param string[]                                 $access
     * @param bool                                     $skipMethodsWithLeadingUnderscore
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param string[][]                               $error
     *
     * @return string[][]
     */
    private static function checkProperties(
        \voku\SimplePhpParser\Model\BasePHPClass $class,
        array                                    $access,
        bool                                     $skipMethodsWithLeadingUnderscore,
        bool                                     $skipAmbiguousTypesAsError,
        array                                    $error
    ): array
    {
        foreach ($class->getPropertiesInfo(
            $access,
            $skipMethodsWithLeadingUnderscore
        ) as $propertyName => $propertyTypes) {
            // reset
            $typeFound = false;

            if (
                $propertyTypes['typeFromPhpDocMaybeWithComment']
                &&
                \strpos($propertyTypes['typeFromPhpDocMaybeWithComment'], '<phpdoctor-ignore-this-line/>') !== false
            ) {
                continue;
            }

            foreach ($propertyTypes as $key => $type) {
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
                if ($propertyTypes['typeFromPhpDocSimple'] && $propertyTypes['type']) {
                    $error = CheckPhpDocType::checkPhpDocType(
                        $propertyTypes,
                        ['file' => $class->file, 'line' => $class->line ?? null],
                        ($class->name ?? '?'),
                        $error,
                        ($class->name ?? null),
                        null,
                        $propertyName
                    );
                }
            } else {
                $error[$class->file ?? ''][] = '[' . ($class->line ?? '?') . ']: missing property type for ' . ($class->name ?? '?') . '->$' . $propertyName;
            }
        }

        return $error;
    }
}
