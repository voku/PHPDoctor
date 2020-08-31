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
        array $access,
        bool $skipDeprecatedMethods,
        bool $skipMethodsWithLeadingUnderscore,
        bool $skipAmbiguousTypesAsError,
        bool $skipParseErrorsAsError,
        array $error
    ): array {
        foreach ($phpInfo->getClasses() as $class) {
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
     * @param \voku\SimplePhpParser\Model\PHPClass $class
     * @param array                                $access
     * @param bool                                 $skipDeprecatedMethods
     * @param bool                                 $skipMethodsWithLeadingUnderscore
     * @param bool                                 $skipAmbiguousTypesAsError
     * @param bool                                 $skipParseErrorsAsError
     * @param string[][]                           $error
     *
     * @return string[][]
     */
    private static function checkMethods(
        \voku\SimplePhpParser\Model\PHPClass $class,
        array $access,
        bool $skipDeprecatedMethods,
        bool $skipMethodsWithLeadingUnderscore,
        bool $skipAmbiguousTypesAsError,
        bool $skipParseErrorsAsError,
        array $error
    ): array {
        foreach ($class->getMethodsInfo(
            $access,
            $skipDeprecatedMethods,
            $skipMethodsWithLeadingUnderscore
        ) as $methodName => $methodInfo) {
            foreach ($methodInfo['paramsTypes'] as $paramName => $paramTypes) {
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

                if (!$skipParseErrorsAsError && $methodInfo['error']) {
                    $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: ' . $methodInfo['error'];
                }
            }
        }

        return $error;
    }

    /**
     * @param \voku\SimplePhpParser\Model\PHPClass $class
     * @param array                                $access
     * @param bool                                 $skipMethodsWithLeadingUnderscore
     * @param bool                                 $skipAmbiguousTypesAsError
     * @param array                                $error
     *
     * @return string[][]
     */
    private static function checkProperties(
        \voku\SimplePhpParser\Model\PHPClass $class,
        array $access,
        bool $skipMethodsWithLeadingUnderscore,
        bool $skipAmbiguousTypesAsError,
        array $error
    ): array {
        foreach ($class->getPropertiesInfo(
            $access,
            $skipMethodsWithLeadingUnderscore
        ) as $propertyName => $propertyTypes) {
            // reset
            $typeFound = false;
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
