<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class Diagnostic
{
    private readonly string $id;

    private readonly string $file;

    private readonly ?int $line;

    /**
     * @var array<string, bool|float|int|string|null>
     */
    private readonly array $evidence;

    /**
     * @param array<string, bool|float|int|string|null> $evidence
     */
    public function __construct(string $id, string $file, ?int $line = null, array $evidence = [])
    {
        $this->id = $id;
        $this->file = $file;
        $this->line = $line;
        $this->evidence = $evidence;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): ?int
    {
        return $this->line;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public function evidence(): array
    {
        return $this->evidence;
    }
}
