<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Finding;

final class FindingFingerprint
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromFinding(Finding $finding): self
    {
        return self::fromDetails(
            $finding->file(),
            $finding->category(),
            $finding->line(),
            $finding->message()
        );
    }

    public static function fromDetails(string $file, FindingCategory $category, ?int $line, string $message): self
    {
        $normalizedMessage = \preg_replace('/^\[\d+\]:\s*/', '', $message);
        \assert(\is_string($normalizedMessage));

        return new self(
            \hash(
                'sha256',
                \json_encode(
                    [
                        'file' => $file,
                        'category' => $category->value(),
                        'line' => $line,
                        'message' => $normalizedMessage,
                    ],
                    \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES
                )
            )
        );
    }

    public function toString(): string
    {
        return $this->value;
    }
}
