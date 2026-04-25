<?php

declare(strict_types=1);

namespace voku\tests;

use Symfony\Component\Console\Output\BufferedOutput;
use voku\PHPDoctor\Report\GithubActionsReporter;
use voku\PHPDoctor\Report\JsonProfileReporter;
use voku\PHPDoctor\Report\TextProfileReporter;

/**
 * @internal
 */
final class ReporterTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonProfileReporterPreservesPrettyPrintedUnescapedJson(): void
    {
        $output = new BufferedOutput();

        JsonProfileReporter::write(
            $output,
            [
                'tool' => 'phpdoctor',
                'path' => 'src/Foo.php',
            ]
        );

        static::assertSame(
            "{\n    \"tool\": \"phpdoctor\",\n    \"path\": \"src/Foo.php\"\n}\n",
            $output->fetch()
        );
    }

    public function testTextProfileReporterPreservesAnalysisOutput(): void
    {
        $output = new BufferedOutput();

        TextProfileReporter::configureStyles($output);
        TextProfileReporter::writeBanner($output, ['src/Foo.php']);
        $errorCount = TextProfileReporter::writeAnalysis(
            $output,
            [
                'src/Foo.php' => [
                    '[3]: missing property type for Example::$foo',
                ],
            ],
            [
                'new_error_count' => 1,
                'summary' => [
                    'missing_native_type' => 1,
                    'other' => 0,
                ],
            ],
            true,
            true
        );

        static::assertSame(1, $errorCount);
        static::assertSame(
            <<<'TEXT'
===============================
List of errors in : src/Foo.php
===============================

src/Foo.php (1 errors)
[3]: missing property type for Example::$foo

-------------------------------
1 errors detected.
1 new errors detected.
-------------------------------

PHPDoctor type and PHPDoc quality profile
- missing_native_type: 1

TEXT,
            $output->fetch()
        );
    }

    public function testGithubActionsReporterEscapesWorkflowCommandValues(): void
    {
        $output = new BufferedOutput();

        GithubActionsReporter::write(
            $output,
            [
                'findings' => [
                    [
                        'file' => "src/Foo:Bar,Baz%\nQux.php",
                        'line' => 12,
                        'category' => 'missing_native_type',
                        'message' => "Problem: bad, worse%\r\nnext",
                        'fingerprint' => 'abc123',
                    ],
                ],
                'new_findings' => [],
            ],
            false
        );

        static::assertSame(
            "::error file=src/Foo%3ABar%2CBaz%25%0AQux.php,line=12::Problem%3A bad%2C worse%25%0D%0Anext (missing_native_type)\n",
            $output->fetch()
        );
    }
}
