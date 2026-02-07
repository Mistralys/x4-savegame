<?php

declare(strict_types=1);

namespace X4\Savegame;

use X4\Savegame\Exception\AncestorExtractionException;

class AncestorFormatter
{
    private const INDENT_SPACES = 2;

    /**
     * Format and write the ancestor chain to a file.
     *
     * @param array $ancestors Array of ancestor elements
     * @param string $outputPath Path to the output file
     * @throws AncestorExtractionException
     */
    public function format(array $ancestors, string $outputPath): void
    {
        $this->validateOutputPath($outputPath);

        $output = $this->buildOutput($ancestors);

        if (file_put_contents($outputPath, $output) === false) {
            throw new AncestorExtractionException("Failed to write output file: {$outputPath}");
        }
    }

    private function validateOutputPath(string $outputPath): void
    {
        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new AncestorExtractionException("Failed to create output directory: {$directory}");
            }
        }

        if (file_exists($outputPath) && !is_writable($outputPath)) {
            throw new AncestorExtractionException("Output file is not writable: {$outputPath}");
        }
    }

    private function buildOutput(array $ancestors): string
    {
        $lines = [];

        foreach ($ancestors as $ancestor) {
            $indent = str_repeat(' ', $ancestor['depth'] * self::INDENT_SPACES);
            $line = $indent . '<' . $ancestor['name'];

            // Add attributes
            foreach ($ancestor['attributes'] as $name => $value) {
                $line .= ' ' . $name . '="' . htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '"';
            }

            $line .= '>';
            $lines[] = $line;
        }

        return implode("\n", $lines) . "\n";
    }
}

