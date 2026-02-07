<?php

declare(strict_types=1);

namespace X4\Savegame\Tests;

use PHPUnit\Framework\TestCase;
use X4\Savegame\AncestorFormatter;
use X4\Savegame\Exception\AncestorExtractionException;

class AncestorFormatterTest extends TestCase
{
    private string $tempDir;
    private AncestorFormatter $formatter;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/x4-savegame-tests';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        $this->formatter = new AncestorFormatter();
    }

    protected function tearDown(): void
    {
        // Clean up temporary test files recursively
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testFormatSimpleAncestorChain(): void
    {
        $ancestors = [
            [
                'name' => 'root',
                'attributes' => [],
                'depth' => 0
            ],
            [
                'name' => 'child',
                'attributes' => ['id' => '123'],
                'depth' => 1
            ]
        ];

        $outputPath = $this->tempDir . '/output.xml';
        $this->formatter->format($ancestors, $outputPath);

        $this->assertFileExists($outputPath);

        $content = file_get_contents($outputPath);
        $expectedContent = "<root>\n  <child id=\"123\">\n";

        $this->assertEquals($expectedContent, $content);
    }

    public function testFormatWithMultipleAttributes(): void
    {
        $ancestors = [
            [
                'name' => 'component',
                'attributes' => [
                    'class' => 'station',
                    'macro' => 'test_macro',
                    'id' => '[0x123]'
                ],
                'depth' => 0
            ]
        ];

        $outputPath = $this->tempDir . '/output.xml';
        $this->formatter->format($ancestors, $outputPath);

        $content = file_get_contents($outputPath);

        $this->assertStringContainsString('<component', $content);
        $this->assertStringContainsString('class="station"', $content);
        $this->assertStringContainsString('macro="test_macro"', $content);
        $this->assertStringContainsString('id="[0x123]"', $content);
    }

    public function testFormatWithProperIndentation(): void
    {
        $ancestors = [
            [
                'name' => 'level1',
                'attributes' => [],
                'depth' => 0
            ],
            [
                'name' => 'level2',
                'attributes' => [],
                'depth' => 1
            ],
            [
                'name' => 'level3',
                'attributes' => [],
                'depth' => 2
            ],
            [
                'name' => 'level4',
                'attributes' => [],
                'depth' => 3
            ]
        ];

        $outputPath = $this->tempDir . '/output.xml';
        $this->formatter->format($ancestors, $outputPath);

        $content = file_get_contents($outputPath);
        $lines = explode("\n", trim($content));

        $this->assertEquals('<level1>', $lines[0]);
        $this->assertEquals('  <level2>', $lines[1]);
        $this->assertEquals('    <level3>', $lines[2]);
        $this->assertEquals('      <level4>', $lines[3]);
    }

    public function testFormatEscapesSpecialCharacters(): void
    {
        $ancestors = [
            [
                'name' => 'element',
                'attributes' => [
                    'text' => 'Text with "quotes" & <special> chars'
                ],
                'depth' => 0
            ]
        ];

        $outputPath = $this->tempDir . '/output.xml';
        $this->formatter->format($ancestors, $outputPath);

        $content = file_get_contents($outputPath);

        $this->assertStringContainsString('&quot;', $content);
        $this->assertStringContainsString('&amp;', $content);
        $this->assertStringContainsString('&lt;', $content);
        $this->assertStringContainsString('&gt;', $content);
    }

    public function testCreateOutputDirectory(): void
    {
        $nestedDir = $this->tempDir . '/nested/deep/path';
        $outputPath = $nestedDir . '/output.xml';

        $ancestors = [
            ['name' => 'root', 'attributes' => [], 'depth' => 0]
        ];

        $this->formatter->format($ancestors, $outputPath);

        $this->assertFileExists($outputPath);
        $this->assertDirectoryExists($nestedDir);
    }

    public function testRejectUnwritableDirectory(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Permission test not reliable on Windows');
        }

        $readOnlyDir = $this->tempDir . '/readonly';
        mkdir($readOnlyDir, 0555);

        $this->expectException(AncestorExtractionException::class);

        $ancestors = [
            ['name' => 'root', 'attributes' => [], 'depth' => 0]
        ];

        try {
            $this->formatter->format($ancestors, $readOnlyDir . '/subdir/output.xml');
        } finally {
            chmod($readOnlyDir, 0755);
            rmdir($readOnlyDir);
        }
    }
}


