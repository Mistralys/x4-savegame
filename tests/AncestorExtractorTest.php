<?php

declare(strict_types=1);

namespace X4\Savegame\Tests;

use PHPUnit\Framework\TestCase;
use X4\Savegame\AncestorExtractor;
use X4\Savegame\Exception\AncestorExtractionException;

class AncestorExtractorTest extends TestCase
{
    private string $fixtureDir;
    private string $sampleXmlPath;

    protected function setUp(): void
    {
        $this->fixtureDir = __DIR__ . '/fixtures';
        $this->sampleXmlPath = $this->fixtureDir . '/sample.xml';
    }

    public function testExtractRootElement(): void
    {
        $extractor = new AncestorExtractor($this->sampleXmlPath, 2);
        $ancestors = $extractor->extract();

        $this->assertCount(1, $ancestors, 'Should have exactly 1 ancestor (root itself)');
        $this->assertEquals('root', $ancestors[0]['name']);
        $this->assertEquals(0, $ancestors[0]['depth']);
    }

    public function testExtractFirstLevelChild(): void
    {
        // Line 3 is <level1 attr1="value1">
        $extractor = new AncestorExtractor($this->sampleXmlPath, 3);
        $ancestors = $extractor->extract();

        // Debug: print what we got
        $names = array_map(fn($a) => $a['name'], $ancestors);

        $this->assertCount(2, $ancestors, 'Should have root and level1. Got: ' . implode(', ', $names));
        $this->assertEquals('root', $ancestors[0]['name']);
        $this->assertEquals('level1', $ancestors[1]['name']);
    }

    public function testExtractNestedElement(): void
    {
        $extractor = new AncestorExtractor($this->sampleXmlPath, 6);
        $ancestors = $extractor->extract();

        // Should have: root -> level1 -> level2 -> level3 -> level4
        $this->assertCount(5, $ancestors);

        $this->assertEquals('root', $ancestors[0]['name']);
        $this->assertEquals(0, $ancestors[0]['depth']);

        $this->assertEquals('level1', $ancestors[1]['name']);
        $this->assertEquals('value1', $ancestors[1]['attributes']['attr1']);
        $this->assertEquals(1, $ancestors[1]['depth']);

        $this->assertEquals('level2', $ancestors[2]['name']);
        $this->assertEquals('value2', $ancestors[2]['attributes']['attr2']);
        $this->assertEquals('value3', $ancestors[2]['attributes']['attr3']);
        $this->assertEquals(2, $ancestors[2]['depth']);

        $this->assertEquals('level3', $ancestors[3]['name']);
        $this->assertEquals(3, $ancestors[3]['depth']);

        $this->assertEquals('level4', $ancestors[4]['name']);
        $this->assertEquals('target', $ancestors[4]['attributes']['id']);
        $this->assertEquals(4, $ancestors[4]['depth']);
    }

    public function testRejectSelfClosingTag(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('self-closing tag');

        $extractor = new AncestorExtractor($this->sampleXmlPath, 10);
        $extractor->extract();
    }

    public function testRejectClosingTag(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('does not contain a complete opening tag');

        $extractor = new AncestorExtractor($this->sampleXmlPath, 8);
        $extractor->extract();
    }

    public function testRejectLineNumberBeyondFileLength(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('beyond the file length');

        $extractor = new AncestorExtractor($this->sampleXmlPath, 999);
        $extractor->extract();
    }

    public function testRejectInvalidLineNumber(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('Line number must be a positive integer');

        $extractor = new AncestorExtractor($this->sampleXmlPath, 0);
        $extractor->extract();
    }

    public function testRejectNegativeLineNumber(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('Line number must be a positive integer');

        $extractor = new AncestorExtractor($this->sampleXmlPath, -5);
        $extractor->extract();
    }

    public function testRejectNonExistentFile(): void
    {
        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('File not found');

        $extractor = new AncestorExtractor('/nonexistent/file.xml', 1);
        $extractor->extract();
    }

    public function testExtractWithRealSavegameFile(): void
    {
        $savegamePath = __DIR__ . '/../saves/advanced-creative-v8/savegame.info-001.xml';

        if (!file_exists($savegamePath)) {
            $this->markTestSkipped('Real savegame file not available');
        }

        // Test with line 6 which is <patches>
        $extractor = new AncestorExtractor($savegamePath, 6);
        $ancestors = $extractor->extract();

        // Should have: info -> patches
        $this->assertCount(2, $ancestors);
        $this->assertEquals('info', $ancestors[0]['name']);
        $this->assertEquals('patches', $ancestors[1]['name']);
    }

    public function testRejectSelfClosingTagInRealFile(): void
    {
        $savegamePath = __DIR__ . '/../saves/advanced-creative-v8/savegame.info-001.xml';

        if (!file_exists($savegamePath)) {
            $this->markTestSkipped('Real savegame file not available');
        }

        $this->expectException(AncestorExtractionException::class);
        $this->expectExceptionMessage('self-closing tag');

        // Line 3 has <save .../> which is self-closing
        $extractor = new AncestorExtractor($savegamePath, 3);
        $extractor->extract();
    }
}





