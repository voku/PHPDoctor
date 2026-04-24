<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Finding;

final class FindingCategory
{
    public const MISSING_NATIVE_TYPE = 'missing_native_type';
    public const MISSING_PHPDOC_TYPE = 'missing_phpdoc_type';
    public const WRONG_PHPDOC_TYPE = 'wrong_phpdoc_type';
    public const DEPRECATED_DOCUMENTATION = 'deprecated_documentation';
    public const PARSE_ERROR = 'parse_error';
    public const OVERRIDE_CONTRACT = 'override_contract';
    public const OTHER = 'other';

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromMessage(string $message): self
    {
        $messageLower = \strtolower($message);

        if (\str_contains($messageLower, 'missing @deprecated tag')) {
            return new self(self::DEPRECATED_DOCUMENTATION);
        }

        if (\str_contains($messageLower, 'invalid #[\override] usage')) {
            return new self(self::OVERRIDE_CONTRACT);
        }

        if (
            \str_contains($messageLower, 'parse')
            || \str_contains($messageLower, 'syntax error')
            || \preg_match('/Unexpected token .* expected .+ on line \d+/i', $message) === 1
        ) {
            return new self(self::PARSE_ERROR);
        }

        if (\preg_match('/wrong (property|parameter|return) type ".+" in phpdoc/', $message) === 1) {
            return new self(self::WRONG_PHPDOC_TYPE);
        }

        if (\preg_match('/missing (property|parameter|return) type ".+" in phpdoc/', $message) === 1) {
            return new self(self::MISSING_PHPDOC_TYPE);
        }

        if (\preg_match('/missing (property|parameter|return) type for /', $message) === 1) {
            return new self(self::MISSING_NATIVE_TYPE);
        }

        return new self(self::OTHER);
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, int>
     */
    public static function summaryTemplate(): array
    {
        return [
            self::MISSING_NATIVE_TYPE => 0,
            self::MISSING_PHPDOC_TYPE => 0,
            self::WRONG_PHPDOC_TYPE => 0,
            self::DEPRECATED_DOCUMENTATION => 0,
            self::PARSE_ERROR => 0,
            self::OVERRIDE_CONTRACT => 0,
            self::OTHER => 0,
        ];
    }
}
