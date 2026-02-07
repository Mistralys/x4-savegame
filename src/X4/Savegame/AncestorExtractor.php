<?php

declare(strict_types=1);

namespace X4\Savegame;

use X4\Savegame\Exception\AncestorExtractionException;
use XMLReader;

class AncestorExtractor
{
    private string $filePath;
    private int $targetLine;
    private array $ancestorStack = [];
    private int $currentLine = 1;
    private bool $targetFound = false;

    public function __construct(string $filePath, int $targetLine)
    {
        $this->filePath = $filePath;
        $this->targetLine = $targetLine;
    }

    /**
     * Extract the ancestor chain for the element at the target line.
     *
     * @return array Array of ancestor elements with their tag names, attributes, and depth
     * @throws AncestorExtractionException
     */
    public function extract(): array
    {
        $this->validateFile();
        $this->validateLineNumber();
        $this->validateCompleteOpeningTag();

        $reader = new XMLReader();

        if (!$reader->open($this->filePath)) {
            throw new AncestorExtractionException("Failed to open XML file: {$this->filePath}");
        }

        try {
            $this->processXml($reader);
        } finally {
            $reader->close();
        }

        if (!$this->targetFound) {
            throw new AncestorExtractionException(
                "Line {$this->targetLine} does not contain a valid opening tag or is beyond the file length"
            );
        }

        return $this->ancestorStack;
    }

    private function validateFile(): void
    {
        if (!file_exists($this->filePath)) {
            throw new AncestorExtractionException("File not found: {$this->filePath}");
        }

        if (!is_readable($this->filePath)) {
            throw new AncestorExtractionException("File is not readable: {$this->filePath}");
        }
    }

    private function validateLineNumber(): void
    {
        if ($this->targetLine < 1) {
            throw new AncestorExtractionException("Line number must be a positive integer");
        }
    }

    private function processXml(XMLReader $reader): void
    {
        $elementStack = [];
        $depth = 0;

        // Read file to get line content
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES);
        $targetLineContent = isset($lines[$this->targetLine - 1]) ? trim($lines[$this->targetLine - 1]) : null;

        if (!$targetLineContent) {
            return;
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                // Get element info
                $element = [
                    'name' => $reader->name,
                    'attributes' => $this->getAttributes($reader),
                    'depth' => $depth,
                    'selfClosing' => $reader->isEmptyElement
                ];

                // Reconstruct the opening tag to match against target line
                $tag = '<' . $element['name'];
                foreach ($element['attributes'] as $name => $value) {
                    $tag .= ' ' . $name . '="' . $value . '"';
                }
                $tag .= $element['selfClosing'] ? ' />' : '>';

                // Check if this matches our target line
                if (trim($tag) === $targetLineContent) {
                    // Found the target! Store the complete ancestor chain
                    $this->ancestorStack = $elementStack;
                    $this->ancestorStack[] = $element;
                    $this->targetFound = true;
                    return;
                }

                // If not self-closing, add to stack for potential ancestors
                if (!$element['selfClosing']) {
                    $elementStack[] = $element;
                    $depth++;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if (!empty($elementStack)) {
                    array_pop($elementStack);
                    $depth--;
                }
            }
        }
    }

    private function getAttributes(XMLReader $reader): array
    {
        $attributes = [];

        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                $attributes[$reader->name] = $reader->value;
            }
            $reader->moveToElement();
        }

        return $attributes;
    }

    private function validateCompleteOpeningTag(): void
    {
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES);

        if (!isset($lines[$this->targetLine - 1])) {
            throw new AncestorExtractionException(
                "Line {$this->targetLine} is beyond the file length"
            );
        }

        $line = trim($lines[$this->targetLine - 1]);

        // Check for self-closing tag first
        if (str_ends_with($line, '/>')) {
            throw new AncestorExtractionException(
                "Line {$this->targetLine} contains a self-closing tag, which is not allowed"
            );
        }

        // Check if line starts with < and ends with > (and not </ for closing tags)
        if (!preg_match('/^<[^\/][^>]*>$/', $line)) {
            throw new AncestorExtractionException(
                "Line {$this->targetLine} does not contain a complete opening tag on a single line"
            );
        }
    }
}




