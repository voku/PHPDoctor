<?php declare(strict_types=1);

namespace voku\PHPDoctor\PhpDocCheck;

use voku\PHPDoctor\Diagnostic\Diagnostic;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticId;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;

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
        $result = self::checkClassesWithDiagnostics(
            $phpInfo,
            $access,
            $skipDeprecatedMethods,
            $skipMethodsWithLeadingUnderscore,
            $skipAmbiguousTypesAsError,
            $skipParseErrorsAsError,
            $error,
            DiagnosticCollection::empty()
        );

        foreach ($result['diagnostics']->all() as $diagnostic) {
            $result['errors'][$diagnostic->file()][] = DiagnosticToLegacyMessageMapper::map($diagnostic);
        }

        foreach ($result['errors'] as &$errorsInner) {
            \natsort($errorsInner);
            $errorsInner = \array_values($errorsInner);
        }

        return $result['errors'];
    }

    /**
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param string[]                                             $access
     * @param bool                                                 $skipDeprecatedMethods
     * @param bool                                                 $skipMethodsWithLeadingUnderscore
     * @param bool                                                 $skipAmbiguousTypesAsError
     * @param bool                                                 $skipParseErrorsAsError
     * @param string[][]                                           $error
     *
     * @return array{
     *     errors: array<string, array<int, string>>,
     *     diagnostics: DiagnosticCollection
     * }
     */
    public static function checkClassesWithDiagnostics(
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        array                                                $access,
        bool                                                 $skipDeprecatedMethods,
        bool                                                 $skipMethodsWithLeadingUnderscore,
        bool                                                 $skipAmbiguousTypesAsError,
        bool                                                 $skipParseErrorsAsError,
        array                                                $error,
        DiagnosticCollection                                 $diagnostics
    ): array {
        foreach (array_merge($phpInfo->getTraits(), $phpInfo->getClasses()) as $class) {
            $diagnostics = self::checkDeprecatedAttributeOnClassLikeElement(
                $class,
                $diagnostics
            );

            $error = self::checkConstantsDeprecatedAttribute(
                $class,
                $error
            );

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
                $error,
                $diagnostics
            );
        }

        foreach ($phpInfo->getInterfaces() as $interface) {
            $diagnostics = self::checkDeprecatedAttributeOnClassLikeElement(
                $interface,
                $diagnostics
            );

            $error = self::checkConstantsDeprecatedAttribute(
                $interface,
                $error
            );

            $error = self::checkMethods(
                $interface,
                $phpInfo,
                $access,
                $skipDeprecatedMethods,
                $skipMethodsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $error,
                $diagnostics
            );
        }

        foreach ($phpInfo->getEnums() as $enum) {
            $diagnostics = self::checkDeprecatedAttributeOnClassLikeElement(
                $enum,
                $diagnostics
            );

            $error = self::checkConstantsDeprecatedAttribute(
                $enum,
                $error
            );

            $error = self::checkMethods(
                $enum,
                $phpInfo,
                $access,
                $skipDeprecatedMethods,
                $skipMethodsWithLeadingUnderscore,
                $skipAmbiguousTypesAsError,
                $skipParseErrorsAsError,
                $error,
                $diagnostics
            );
        }

        return [
            'errors' => $error,
            'diagnostics' => $diagnostics,
        ];
    }

    /**
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class
     * @param \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
     * @param string[]                                 $access
     * @param bool                                     $skipDeprecatedMethods
     * @param bool                                     $skipMethodsWithLeadingUnderscore
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param bool                                     $skipParseErrorsAsError
     * @param string[][]                               $error
     * @param DiagnosticCollection                     $diagnostics
     *
     * @return string[][]
     */
    private static function checkMethods(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo,
        array                                    $access,
        bool                                     $skipDeprecatedMethods,
        bool                                     $skipMethodsWithLeadingUnderscore,
        bool                                     $skipAmbiguousTypesAsError,
        bool                                     $skipParseErrorsAsError,
        array                                    $error,
        DiagnosticCollection                     &$diagnostics
    ): array
    {
        foreach (self::getMethodsInfoFromElement(
            $class,
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

            $method = $class->methods[$methodName] ?? null;
            if ($method instanceof \voku\SimplePhpParser\Model\PHPMethod) {
                $diagnostics = self::checkDeprecatedAttributeOnMethod(
                    $method,
                    ($class->name ?? '?') . ($methodInfo['is_static'] ? '::' : '->') . $methodName . '()',
                    $diagnostics
                );
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
     * @param string[] $access
     *
     * @return array<string, array{
     *     fullDescription: string,
     *     line: null|int,
     *     file: null|string,
     *     error: string,
     *     is_deprecated: bool,
     *     is_static: null|bool,
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
     * }>
     */
    private static function getMethodsInfoFromElement(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
        array $access,
        bool $skipDeprecatedMethods,
        bool $skipMethodsWithLeadingUnderscore
    ): array {
        if (
            $class instanceof \voku\SimplePhpParser\Model\PHPClass
            ||
            $class instanceof \voku\SimplePhpParser\Model\PHPTrait
        ) {
            return $class->getMethodsInfo(
                $access,
                $skipDeprecatedMethods,
                $skipMethodsWithLeadingUnderscore
            );
        }

        return self::buildMethodInfoList(
            $class->methods,
            $class->line,
            $class->file,
            $access,
            $skipDeprecatedMethods,
            $skipMethodsWithLeadingUnderscore
        );
    }

    /**
     * @param array<string, \voku\SimplePhpParser\Model\PHPMethod> $methods
     * @param string[]                                             $access
     *
     * @return array<string, array{
     *     fullDescription: string,
     *     line: null|int,
     *     file: null|string,
     *     error: string,
     *     is_deprecated: bool,
     *     is_static: null|bool,
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
     * }>
     */
    private static function buildMethodInfoList(
        array $methods,
        ?int $fallbackLine,
        ?string $fallbackFile,
        array $access,
        bool $skipDeprecatedMethods,
        bool $skipMethodsWithLeadingUnderscore
    ): array {
        $allInfo = [];

        foreach ($methods as $method) {
            if (!\in_array($method->access, $access, true)) {
                continue;
            }

            if ($skipDeprecatedMethods && $method->hasDeprecatedTag) {
                continue;
            }

            if ($skipMethodsWithLeadingUnderscore && \strpos($method->name, '_') === 0) {
                continue;
            }

            $allInfo[$method->name] = self::buildMethodInfo(
                $method,
                $fallbackLine,
                $fallbackFile
            );
        }

        \asort($allInfo);

        return $allInfo;
    }

    /**
     * @return array{
     *     fullDescription: string,
     *     line: null|int,
     *     file: null|string,
     *     error: string,
     *     is_deprecated: bool,
     *     is_static: null|bool,
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
     * }
     */
    private static function buildMethodInfo(
        \voku\SimplePhpParser\Model\PHPMethod $method,
        ?int $fallbackLine,
        ?string $fallbackFile
    ): array {
        $paramsTypes = [];
        foreach ($method->parameters as $tagParam) {
            $paramsTypes[$tagParam->name]['type'] = $tagParam->type;
            $paramsTypes[$tagParam->name]['typeFromPhpDocMaybeWithComment'] = $tagParam->typeFromPhpDocMaybeWithComment;
            $paramsTypes[$tagParam->name]['typeFromPhpDoc'] = $tagParam->typeFromPhpDoc;
            $paramsTypes[$tagParam->name]['typeFromPhpDocSimple'] = $tagParam->typeFromPhpDocSimple;
            $paramsTypes[$tagParam->name]['typeFromPhpDocExtended'] = $tagParam->typeFromPhpDocExtended;
            $paramsTypes[$tagParam->name]['typeFromDefaultValue'] = $tagParam->typeFromDefaultValue;
        }

        $returnTypes = [];
        $returnTypes['type'] = $method->returnType;
        $returnTypes['typeFromPhpDocMaybeWithComment'] = $method->returnTypeFromPhpDocMaybeWithComment;
        $returnTypes['typeFromPhpDoc'] = $method->returnTypeFromPhpDoc;
        $returnTypes['typeFromPhpDocSimple'] = $method->returnTypeFromPhpDocSimple;
        $returnTypes['typeFromPhpDocExtended'] = $method->returnTypeFromPhpDocExtended;

        $paramsPhpDocRaw = [];
        foreach ($method->parameters as $tagParam) {
            $paramsPhpDocRaw[$tagParam->name] = $tagParam->phpDocRaw;
        }

        $info = [];
        $info['fullDescription'] = \trim($method->summary . "\n\n" . $method->description);
        $info['paramsTypes'] = $paramsTypes;
        $info['returnTypes'] = $returnTypes;
        $info['paramsPhpDocRaw'] = $paramsPhpDocRaw;
        $info['returnPhpDocRaw'] = $method->returnPhpDocRaw;
        $info['line'] = $method->line ?? $fallbackLine;
        $info['file'] = $method->file ?? $fallbackFile;
        $info['error'] = \implode("\n", $method->parseError);
        foreach ($method->parameters as $parameter) {
            $info['error'] .= ($info['error'] ? "\n" : '') . \implode("\n", $parameter->parseError);
        }
        $info['is_deprecated'] = $method->hasDeprecatedTag;
        $info['is_static'] = $method->is_static;
        $info['is_meta'] = $method->hasMetaTag;
        $info['is_internal'] = $method->hasInternalTag;
        $info['is_removed'] = $method->hasRemovedTag;

        return $info;
    }

    /**
     * @param array                                    $methodInfo
     * @param string                                   $methodName
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class
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
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
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
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
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

        if ($class instanceof \voku\SimplePhpParser\Model\PHPInterface) {
            foreach ($class->parentInterfaces as $interfaceName) {
                if (self::interfaceOrParentsHasMethod($interfaceName, $methodName, $phpInfo)) {
                    return true;
                }
            }
        }

        if ($class instanceof \voku\SimplePhpParser\Model\PHPEnum) {
            foreach ($class->interfaces as $interfaceName) {
                if (self::interfaceOrParentsHasMethod($interfaceName, $methodName, $phpInfo)) {
                    return true;
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
     * @param class-string $interfaceName
     */
    private static function interfaceOrParentsHasMethod(
        string $interfaceName,
        string $methodName,
        \voku\SimplePhpParser\Parsers\Helper\ParserContainer $phpInfo
    ): bool {
        $interface = $phpInfo->getInterface($interfaceName);
        if (
            $interface
            &&
            isset($interface->methods[$methodName])
        ) {
            return true;
        }

        if ($interface) {
            foreach ($interface->parentInterfaces as $parentInterfaceName) {
                if (self::interfaceOrParentsHasMethod($parentInterfaceName, $methodName, $phpInfo)) {
                    return true;
                }
            }
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

        return false;
    }

    /**
     * @return DiagnosticCollection
     */
    private static function checkDeprecatedAttributeOnClassLikeElement(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
        DiagnosticCollection $diagnostics
    ): DiagnosticCollection {
        if (
            !AttributeHelper::hasAttributeNamed($class->attributes, 'Deprecated')
            ||
            $class->hasDeprecatedTag
        ) {
            return $diagnostics;
        }

        return $diagnostics->with(
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                $class->file ?? '',
                $class->line,
                ['display_name' => $class->name ?? '?']
            )
        );
    }

    /**
     * @return DiagnosticCollection
     */
    private static function checkDeprecatedAttributeOnMethod(
        \voku\SimplePhpParser\Model\PHPMethod $method,
        string $methodDisplayName,
        DiagnosticCollection $diagnostics
    ): DiagnosticCollection {
        if (
            !AttributeHelper::hasAttributeNamed($method->attributes, 'Deprecated')
            ||
            $method->hasDeprecatedTag
        ) {
            return $diagnostics;
        }

        return $diagnostics->with(
            new Diagnostic(
                DiagnosticId::DEPRECATED_ATTRIBUTE_MISSING_PHPDOC_TAG,
                $method->file ?? '',
                $method->line,
                ['display_name' => $methodDisplayName]
            )
        );
    }

    /**
     * Checks all constants of a class-like element for a #[\Deprecated] attribute
     * without a corresponding @deprecated phpdoc tag.
     *
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class
     * @param string[][] $error
     *
     * @return string[][]
     */
    private static function checkConstantsDeprecatedAttribute(
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
        array $error
    ): array {
        foreach ($class->constants as $const) {
            $error = self::checkDeprecatedAttributeOnConstant(
                $const,
                ($class->name ?? '?') . '::' . $const->name,
                $error
            );
        }

        return $error;
    }

    /**
     * Checks a single class constant for a #[\Deprecated] attribute without a
     * corresponding @deprecated phpdoc tag.
     *
     * The library's hasDeprecatedTag field is unreliable for class constants
     * because collectTags() is called on the Const_ child node rather than the
     * ClassConst parent node that carries the doc-comment. This method works
     * around that limitation by using reflection when the owning class is
     * already loaded in the current process.
     *
     * @param \voku\SimplePhpParser\Model\PHPConst $const
     * @param string $displayName
     * @param string[][] $error
     *
     * @return string[][]
     */
    private static function checkDeprecatedAttributeOnConstant(
        \voku\SimplePhpParser\Model\PHPConst $const,
        string $displayName,
        array $error
    ): array {
        if (!AttributeHelper::hasAttributeNamed($const->attributes, 'Deprecated')) {
            return $error;
        }

        if ($const->parentName !== null) {
            $parentName = $const->parentName;

            if (
                !class_exists($parentName, false)
                && !interface_exists($parentName, false)
                && !trait_exists($parentName, false)
                && !(\function_exists('enum_exists') && \enum_exists($parentName, false))
            ) {
                // The owning type is not loaded – skip to avoid false positives.
                return $error;
            }

            try {
                $reflConst = new \ReflectionClassConstant($parentName, $const->name);
                $docComment = $reflConst->getDocComment();
                if ($docComment !== false && \stripos($docComment, '@deprecated') !== false) {
                    return $error;
                }
            } catch (\Throwable $e) {
                // Reflection failed for an unexpected reason – skip to be safe.
                return $error;
            }
        } elseif ($const->hasDeprecatedTag) {
            return $error;
        }

        $error[$const->file ?? ''][] = '[' . ($const->line ?? '?') . ']: missing @deprecated tag in phpdoc from ' . $displayName;

        return $error;
    }

    /**
     * Checks a single class property for a #[\Deprecated] attribute without a
     * corresponding @deprecated phpdoc tag.
     *
     * PHPProperty does not implement PHPDocElement, so hasDeprecatedTag is not
     * available. This method falls back to reflection when the owning class is
     * already loaded in the current process.
     *
     * @param \voku\SimplePhpParser\Model\PHPProperty $property
     * @param string $className
     * @param string $displayName
     * @param string[][] $error
     *
     * @return string[][]
     */
    private static function checkDeprecatedAttributeOnProperty(
        \voku\SimplePhpParser\Model\PHPProperty $property,
        string $className,
        string $displayName,
        array $error
    ): array {
        if (!AttributeHelper::hasAttributeNamed($property->attributes, 'Deprecated')) {
            return $error;
        }

        if (
            !class_exists($className, false)
            && !trait_exists($className, false)
        ) {
            // The owning type is not loaded – skip to avoid false positives.
            return $error;
        }

        try {
            $reflProp = new \ReflectionProperty($className, $property->name);
            $docComment = $reflProp->getDocComment();
            if ($docComment !== false && \stripos($docComment, '@deprecated') !== false) {
                return $error;
            }
        } catch (\Throwable $e) {
            // Reflection failed for an unexpected reason – skip to be safe.
            return $error;
        }

        $error[$property->file ?? ''][] = '[' . ($property->line ?? '?') . ']: missing @deprecated tag in phpdoc from ' . $displayName;

        return $error;
    }

    /**
     * @param array                                    $methodInfo
     * @param bool                                     $skipAmbiguousTypesAsError
     * @param \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class
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
        \voku\SimplePhpParser\Model\PHPClass|\voku\SimplePhpParser\Model\PHPTrait|\voku\SimplePhpParser\Model\PHPInterface|\voku\SimplePhpParser\Model\PHPEnum $class,
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

            $property = $class->properties[$propertyName] ?? null;

            if ($property !== null) {
                $error = self::checkDeprecatedAttributeOnProperty(
                    $property,
                    $class->name ?? '?',
                    ($class->name ?? '?') . '->$' . $propertyName,
                    $error
                );
            }

            $propertyPhpDocRaw = $property !== null ? $property->phpDocRaw : null;

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
