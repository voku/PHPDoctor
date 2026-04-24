<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Analysis;

use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;

final class AnalysisResult
{
    private readonly DiagnosticCollection $diagnostics;

    /**
     * @var null|array<string, list<string>>
     */
    private readonly ?array $legacyErrors;

    /**
     * @param null|array<string, list<string>> $legacyErrors
     */
    public function __construct(DiagnosticCollection $diagnostics, ?array $legacyErrors = null)
    {
        $this->diagnostics = $diagnostics;
        $this->legacyErrors = $legacyErrors;
    }

    public function diagnostics(): DiagnosticCollection
    {
        return $this->diagnostics;
    }

    /**
     * @return array<string, list<string>>
     */
    public function toLegacyErrors(): array
    {
        if ($this->legacyErrors !== null) {
            return $this->legacyErrors;
        }

        $errors = [];
        foreach ($this->diagnostics->all() as $diagnostic) {
            $errors[$diagnostic->file()][] = DiagnosticToLegacyMessageMapper::map($diagnostic);
        }

        foreach (\array_keys($errors) as $file) {
            \usort($errors[$file], '\strnatcmp');
        }

        return $errors;
    }
}
