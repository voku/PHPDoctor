<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Diagnostic;

final class DiagnosticCollection
{
    /**
     * @var list<Diagnostic>
     */
    private readonly array $diagnostics;

    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(array $diagnostics = [])
    {
        $this->diagnostics = $diagnostics;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(Diagnostic $diagnostic): self
    {
        $diagnostics = $this->diagnostics;
        $diagnostics[] = $diagnostic;

        return new self($diagnostics);
    }

    /**
     * @return list<Diagnostic>
     */
    public function all(): array
    {
        return $this->diagnostics;
    }

    public function isEmpty(): bool
    {
        return $this->diagnostics === [];
    }
}
