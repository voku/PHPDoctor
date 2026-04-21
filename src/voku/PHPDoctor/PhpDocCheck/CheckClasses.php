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
                $phpInfo,
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
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
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
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
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

            // INFO: ignore "missing type for Exception"
            if (is_a(($class->name ?? ''), \Exception::class, true)) {
              return $error;
            }

            if (!$skipParseErrorsAsError && $methodInfo['error']) {
                $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: ' . str_replace("\n", ' ', $methodInfo['error']);
            }

            $error = self::checkInvalidOverrideUsage(
                $methodInfo,
                $methodName,
                $class,
                $phpInfo,
                $error
            );

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
     * @param string                                   $methodName
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param string[][]                               $error
     *
     * @return string[][]
     *
     * @phpstan-param array{
     *     line: null|int,
     *     file: null|string,
     *     is_static: null|bool
     * } $methodInfo
     */
    private static function checkInvalidOverrideUsage(
        array $methodInfo,
        string $methodName,
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        array $error
    ): array {
        $method = $class->methods[$methodName] ?? null;
        if (
            !($method instanceof \voku\SimplePhpParser\Model\PHPMethod)
            ||
            $method->is_override !== true
        ) {
            return $error;
        }

        if (self::hasParentOrInterfaceMethod($class, $methodName, $phpInfo)) {
            return $error;
        }

        $error[$methodInfo['file'] ?? ''][] = '[' . ($methodInfo['line'] ?? '?') . ']: invalid #[\Override] usage for ' . ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '()';

        return $error;
    }

    private static function hasParentOrInterfaceMethod(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class,
        string $methodName,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
    ): bool {
        if ($class instanceof \voku\SimplePhpParser\Model\PHPClass) {
            if (
                $class->parentClass
                &&
                self::classOrParentsHasMethod($class->parentClass, $methodName, $phpInfo)
            ) {
                return true;
            }

            foreach ($class->interfaces as $interfaceName) {
                $interface = $phpInfo->getInterface($interfaceName);
                if (
                    $interface
                    &&
                    isset($interface->methods[$methodName])
                ) {
                    return true;
                }

                try {
                    if (
                        \interface_exists($interfaceName, true)
                        &&
                        \method_exists($interfaceName, $methodName)
                    ) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    // nothing
                }
            }
        }

        return false;
    }

    /**
     * @param class-string $className
     */
    private static function classOrParentsHasMethod(
        string $className,
        string $methodName,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
    ): bool {
        $class = $phpInfo->getClass($className);
        if (
            $class
            &&
            isset($class->methods[$methodName])
        ) {
            return true;
        }

        if (
            $class
            &&
            $class->parentClass
            &&
            self::classOrParentsHasMethod($class->parentClass, $methodName, $phpInfo)
        ) {
            return true;
        }

        try {
            if (
                \class_exists($className, true)
                &&
                \method_exists($className, $methodName)
            ) {
                return true;
            }
        } catch (\Throwable $e) {
            // nothing
        }

        return false;
    }

    /**
     * @param array                                    $methodInfo
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class
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
     *           type?: null|string,
     *           typeFromPhpDoc?: null|string,
     *           typeFromPhpDocExtended?: null|string,
     *           typeFromPhpDocSimple?: null|string,
     *           typeFromPhpDocMaybeWithComment?: null|string,
     *           typeFromDefaultValue?: null|string
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
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class,
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
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class
     * @param string[]                                 $access
     * @param bool                                     $skipMethodsWithLeadingUnderscore
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param string[][]                               $error
     *
     * @return string[][]
     */
    private static function checkProperties(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait $class,
        array                                    $access,
        bool                                     $skipMethodsWithLeadingUnderscore,
        bool                                     $skipAmbiguousTypesAsError,
        array                                    $error
    ): array
    {
        // INFO: ignore "missing type for Exception"
        if (is_a(($class->name ?? ''), \Exception::class, true)) {
          return $error;
        }

        foreach ($class->getPropertiesInfo(
            $access,
            $skipMethodsWithLeadingUnderscore
        ) as $propertyName => $propertyTypes) {
            // reset
            $typeFound = false;

            $propertyPhpDocRaw = isset($class->properties[$propertyName])
                ? $class->properties[$propertyName]->phpDocRaw
                : null;

            if (
                (
                    $propertyTypes['typeFromPhpDocMaybeWithComment']
                    &&
                    \strpos($propertyTypes['typeFromPhpDocMaybeWithComment'], '<phpdoctor-ignore-this-line/>') !== false
                )
                ||
                (
                    $propertyPhpDocRaw
                    &&
                    \strpos($propertyPhpDocRaw, '<phpdoctor-ignore-this-line/>') !== false
                )
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
