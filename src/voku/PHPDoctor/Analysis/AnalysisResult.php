<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Analysis;

use voku\PHPDoctor\Diagnostic\DiagnosticCollection;
use voku\PHPDoctor\Diagnostic\DiagnosticToFindingMapper;
use voku\PHPDoctor\Diagnostic\DiagnosticToLegacyMessageMapper;
use voku\PHPDoctor\Finding\Finding;

final class AnalysisResult
{
    private readonly DiagnosticCollection $diagnostics;

    /**
     * @var null|array<string, list<string>>
     */
    private readonly ?array $legacyOnlyErrors;

    /**
     * @param null|array<string, list<string>> $legacyOnlyErrors
     */
    public function __construct(DiagnosticCollection $diagnostics, ?array $legacyOnlyErrors = null)
    {
        $this->diagnostics = $diagnostics;
        $this->legacyOnlyErrors = $legacyOnlyErrors;
    }

    public function diagnostics(): DiagnosticCollection
    {
        return $this->diagnostics;
    }

    /**
     * @return array<string, list<string>>
     */
    public function legacyOnlyErrors(): array
    {
        return $this->legacyOnlyErrors ?? [];
    }

    /**
     * @return list<Finding>
     */
    public function findings(): array
    {
        $findings = [];
        foreach ($this->legacyOnlyErrors() as $file => $messages) {
            foreach ($messages as $message) {
                $findings[] = Finding::fromMessage((string) $file, $message);
            }
        }

        foreach ($this->diagnostics->all() as $diagnostic) {
            $findings[] = DiagnosticToFindingMapper::map($diagnostic);
        }

        return $findings;
    }

    /**
     * @return array<string, list<string>>
     */
    public function toLegacyErrors(): array
    {
        $errors = $this->legacyOnlyErrors();
        foreach ($this->diagnostics->all() as $diagnostic) {
            $errors[$diagnostic->file()][] = DiagnosticToLegacyMessageMapper::map($diagnostic);
        }

        foreach (\array_keys($errors) as $file) {
            \usort($errors[$file], '\strnatcmp');
        }
        /** @var array<string, list<string>> $errors */

        return $errors;
    }
}
