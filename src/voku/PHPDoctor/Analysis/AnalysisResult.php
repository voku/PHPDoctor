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
        $diagnosticFindings = [];
        foreach ($this->diagnostics->all() as $diagnostic) {
            $message = DiagnosticToLegacyMessageMapper::map($diagnostic);
            $diagnosticFindings[$diagnostic->file()][$message][] = DiagnosticToFindingMapper::map($diagnostic);
        }

        $findings = [];
        foreach ($this->toLegacyErrors() as $file => $messages) {
            foreach ($messages as $message) {
                if (isset($diagnosticFindings[$file][$message][0])) {
                    /** @var Finding $finding */
                    $finding = \array_shift($diagnosticFindings[$file][$message]);
                    $findings[] = $finding;

                    if ($diagnosticFindings[$file][$message] === []) {
                        unset($diagnosticFindings[$file][$message]);
                    }

                    continue;
                }

                $findings[] = Finding::fromMessage((string) $file, $message);
            }
        }

        foreach ($diagnosticFindings as $findingsByMessage) {
            foreach ($findingsByMessage as $findingsForMessage) {
                foreach ($findingsForMessage as $finding) {
                    $findings[] = $finding;
                }
            }
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
            \natsort($errors[$file]);
            $errors[$file] = \array_values($errors[$file]);
        }
        /** @var array<string, list<string>> $errors */

        return $errors;
    }
}
