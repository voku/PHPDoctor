<?php

declare(strict_types=1);

namespace voku\tests;

use voku\PHPDoctor\Finding\Finding;
use voku\PHPDoctor\Finding\FindingCategory;
use voku\PHPDoctor\Finding\FindingFingerprint;
use voku\PHPDoctor\Profile\QualityProfileBuilder;
use voku\PHPDoctor\QualityProfile;

/**
 * @internal
 */
final class FindingModelTest extends \PHPUnit\Framework\TestCase
{
    public function testFindingSerialization(): void
    {
        $message = '[3]: missing property type for voku\tests\SimpleClass->$foo';
        $finding = Finding::fromMessage('test_file.php', $message);

        static::assertSame(
            [
                'file' => 'test_file.php',
                'line' => 3,
                'category' => 'missing_native_type',
                'message' => $message,
                'fingerprint' => \hash(
                    'sha256',
                    \json_encode(
                        [
                            'file' => 'test_file.php',
                            'category' => 'missing_native_type',
                            'line' => 3,
                            'message' => 'missing property type for voku\tests\SimpleClass->$foo',
                        ],
                        \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES
                    )
                ),
            ],
            $finding->toArray()
        );
    }

    public function testFindingPreservesCategory(): void
    {
        $finding = Finding::fromMessage(
            'test_file.php',
            '[39]: foo_broken:39 | Unexpected token "", expected \'}\' at offset 45 on line 1'
        );

        static::assertSame(FindingCategory::PARSE_ERROR, $finding->category()->value());
        static::assertSame(FindingCategory::PARSE_ERROR, $finding->toArray()['category']);
    }

    public function testFindingPreservesFingerprint(): void
    {
        $message = '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()';
        $category = FindingCategory::fromMessage($message);

        $finding = Finding::fromMessage('test_file.php', $message);
        $fingerprint = FindingFingerprint::fromDetails('test_file.php', $category, 8, $message);

        static::assertSame($fingerprint->toString(), $finding->fingerprint()->toString());
    }

    public function testQualityProfileOutputCompatibility(): void
    {
        $errors = [
            'test_file.php' => [
                '[3]: missing property type for voku\tests\SimpleClass->$foo',
                '[8]: wrong return type "string" in phpdoc from voku\tests\WrongDoc->foo()',
            ],
        ];

        $typedProfile = QualityProfileBuilder::fromErrors($errors)->toArray();
        $legacyProfile = QualityProfile::fromErrors($errors);

        static::assertSame($legacyProfile, $typedProfile);
    }
}
