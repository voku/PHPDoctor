<?php

declare(strict_types=1);

namespace voku\PHPDoctor\Baseline;

use voku\PHPDoctor\Analysis\AnalysisResult;
use voku\PHPDoctor\Diagnostic\DiagnosticCollection;

final class BaselineFlow
{
    /**
     * @return string[]
     *
     * @throws BaselineFlowException
     */
    public static function loadFingerprints(string $baselineFile): array
    {
        if (!\is_file($baselineFile)) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" does not exist.');
        }

        try {
            return BaselineReader::read($baselineFile)->fingerprints();
        } catch (\JsonException) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" does not contain valid JSON.');
        } catch (\UnexpectedValueException) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" does not contain a supported baseline schema.');
        } catch (\RuntimeException) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" could not be read.');
        }
    }

    /**
     * @param array<string, list<string>> $errors
     *
     * @throws BaselineFlowException
     */
    public static function generate(string $baselineFile, array $errors): void
    {
        $validationError = self::validateWriteTarget($baselineFile);
        if ($validationError !== null) {
            throw new BaselineFlowException($validationError);
        }

        $writeResult = BaselineWriter::write($baselineFile, BaselineBuilder::fromErrors($errors));
        if ($writeResult === false) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" could not be written.');
        }
    }

    /**
     * @param array<string, list<string>> $errors
     *
     * Transitional compatibility path for callers that still pass legacy errors plus diagnostics.
     *
     * @throws BaselineFlowException
     */
    public static function generateFromErrorsAndDiagnostics(
        string $baselineFile,
        array $errors,
        DiagnosticCollection $diagnostics
    ): void {
        $validationError = self::validateWriteTarget($baselineFile);
        if ($validationError !== null) {
            throw new BaselineFlowException($validationError);
        }

        $writeResult = BaselineWriter::write(
            $baselineFile,
            BaselineBuilder::fromErrorsAndDiagnostics($errors, $diagnostics)
        );
        if ($writeResult === false) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" could not be written.');
        }
    }

    /**
     * @throws BaselineFlowException
     */
    public static function generateFromAnalysisResult(string $baselineFile, AnalysisResult $analysisResult): void
    {
        $validationError = self::validateWriteTarget($baselineFile);
        if ($validationError !== null) {
            throw new BaselineFlowException($validationError);
        }

        $writeResult = BaselineWriter::write(
            $baselineFile,
            BaselineBuilder::fromAnalysisResult($analysisResult)
        );
        if ($writeResult === false) {
            throw new BaselineFlowException('The baseline-file "' . $baselineFile . '" could not be written.');
        }
    }

    private static function validateWriteTarget(string $baselineFile): ?string
    {
        $directory = \dirname($baselineFile);
        if (!\is_dir($directory)) {
            return 'The baseline-file directory "' . $directory . '" does not exist.';
        }

        if (!\is_writable($directory)) {
            return 'The baseline-file directory "' . $directory . '" is not writable.';
        }

        if (\file_exists($baselineFile) && !\is_writable($baselineFile)) {
            return 'The baseline-file "' . $baselineFile . '" is not writable.';
        }

        return null;
    }
}
