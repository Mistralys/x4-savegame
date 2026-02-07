<?php

declare(strict_types=1);

namespace Command;

use Composer\Script\Event;
use X4\Savegame\AncestorExtractor;
use X4\Savegame\AncestorFormatter;
use X4\Savegame\Exception\AncestorExtractionException;

class ExtractAncestorPath
{
    /**
     * Main entry point for the Composer script.
     *
     * Usage: composer extract-ancestor-path -- <xml-file> <line-number> <output-file>
     */
    public static function main(Event $event): void
    {
        $io = $event->getIO();
        $args = $event->getArguments();

        // Validate arguments
        if (count($args) !== 3) {
            $io->writeError('Usage: composer extract-ancestor-path -- <xml-file> <line-number> <output-file>');
            $io->writeError('');
            $io->writeError('Arguments:');
            $io->writeError('  xml-file     Path to the XML file');
            $io->writeError('  line-number  Line number of the target element (must be an opening tag)');
            $io->writeError('  output-file  Path where the ancestor chain will be written');
            exit(1);
        }

        [$xmlFile, $lineNumberStr, $outputFile] = $args;

        // Validate line number
        if (!is_numeric($lineNumberStr) || (int)$lineNumberStr < 1) {
            $io->writeError("<error>Error: Line number must be a positive integer</error>");
            exit(1);
        }

        $lineNumber = (int)$lineNumberStr;

        try {
            $io->write("Extracting ancestor chain from <info>{$xmlFile}</info> at line <info>{$lineNumber}</info>...");

            // Extract ancestors
            $extractor = new AncestorExtractor($xmlFile, $lineNumber);
            $ancestors = $extractor->extract();

            $io->write("Found <info>" . count($ancestors) . "</info> ancestor(s) in the chain.");

            // Format and write output
            $formatter = new AncestorFormatter();
            $formatter->format($ancestors, $outputFile);

            $io->write("<info>Success!</info> Ancestor chain written to: <comment>{$outputFile}</comment>");

        } catch (AncestorExtractionException $e) {
            $io->writeError("<error>Error: {$e->getMessage()}</error>");
            exit(1);
        } catch (\Exception $e) {
            $io->writeError("<error>Unexpected error: {$e->getMessage()}</error>");
            exit(1);
        }
    }
}

