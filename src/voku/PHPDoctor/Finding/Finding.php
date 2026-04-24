<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Finding;

final class Finding
{
    private readonly string $file;

    private readonly ?int $line;

    private readonly FindingCategory $category;

    private readonly string $message;

    private readonly FindingFingerprint $fingerprint;

    public function __construct(
        string $file,
        ?int $line,
        FindingCategory $category,
        string $message,
        FindingFingerprint $fingerprint
    ) {
        $this->file = $file;
        $this->line = $line;
        $this->category = $category;
        $this->message = $message;
        $this->fingerprint = $fingerprint;
    }

    public static function fromMessage(string $file, string $message): self
    {
        $line = null;
        if (\preg_match('/^\[(\d+)\]: /', $message, $matches) === 1) {
            $line = (int) $matches[1];
        }

        $category = FindingCategory::fromMessage($message);

        return new self(
            $file,
            $line,
            $category,
            $message,
            FindingFingerprint::fromDetails($file, $category, $line, $message)
        );
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): ?int
    {
        return $this->line;
    }

    public function category(): FindingCategory
    {
        return $this->category;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function fingerprint(): FindingFingerprint
    {
        return $this->fingerprint;
    }

    /**
     * @return array{
     *     file: string,
     *     line: null|int,
     *     category: string,
     *     message: string,
     *     fingerprint: string
     * }
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'category' => $this->category->value(),
            'message' => $this->message,
            'fingerprint' => $this->fingerprint->toString(),
        ];
    }
}
