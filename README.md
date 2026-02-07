# X4 Savegame

X4 savegame documentation and data files for XML structure reference.

## Bundled Savegames

- [X4 v8 - Scientist Start](saves/start-scientist-v8.xml.gz) - Game start - 369Mb XML
- [X4 v8 - Creative Advanced](saves/advanced-creative-v8.xml.gz) - 2 days play time - 704Mb XML

For each savegame, the extracted XML fragments (as sliced by the savegame parser) 
are included in a subfolder, in prettified XML format. This is a great resource to
view the structure of the XML.

> NOTE: To keep the project small, the XML files are zipped. 

## Locating Nodes in the XML

For XML parsing tasks, you will often be looking for a specific node, and
need to find out how exactly it is nested in the overall document structure.
The bundled tool to extract tag ancestors is your friend in this case:

1. Identify the file and line number of the target XML tag.
2. Run the tool (see [Extractor Usage](#extractor-usage) below).
3. Use the generated file to view the tag's access path.

> NOTE: An example of this can be seen in [player locations](/docs/player-locations.md).

## Adding Savegames

1. Add the gz save file.
2. Commit to Git.

To also add the XML fragment files:

1. Switch to the savegame parser project.
2. Run `composer update` to load the new save file.
3. Add the save name in the `extract-test-saves.php` script.
4. Run the script.
5. Run the XML indenter on the XML files: `php bin/php/indent-xml.php .\tests\files\test-saves\{SAVE_FOLDER}\XML\ --replace`.
6. Copy the XML files over here into a folder for the save.
7. Compress the XML files to a 7z archive.
8. Commit the archive to Git.

## Tools

### Extract Ancestor Path

A CLI tool to extract the complete ancestor chain of any XML element by 
line number. This is useful for navigating and documenting the complex 
nested structure of X4 savegame files without manually tracing through 
thousands of lines.

#### Installation

First, install Composer dependencies and generate the autoloader:

```bash
composer install
```

#### Extractor Usage

```bash
composer extract-ancestor-path -- <xml-file> <line-number> <output-file>
```

**Arguments:**
- `xml-file` - Path to the XML savegame file (must be extracted, not .gz compressed)
- `line-number` - Line number of the target element (must be a complete opening tag)
- `output-file` - Path where the ancestor chain will be written

**Example:**

```bash
composer extract-ancestor-path -- saves/start-scientist-v8.xml 12345 output/player-location.xml
```

**Output Format:**

The tool generates a formatted XML file showing only the ancestor chain from the root element down to your target element, preserving all attributes and proper indentation:

```xml
<connection connection="cluster_01_connection">
  <component class="cluster" macro="cluster_01_macro" connection="galaxy" code="DDZ-200" knownto="player" known="1" read="0" id="[0x1251]">
    <connections>
      <connection connection="c01s01_region002_connection">
        <component class="region" macro="c01s01_region002_macro" connection="cluster" id="[0x1253]">
          <connection connection="zone004_cluster_01_sector001_connection">
            <component class="zone" macro="zone004_cluster_01_sector001_macro" connection="sector" code="RII-277" knownto="player" id="[0x1313]">
```

**Requirements:**
- The XML file must be valid and prettified (one tag per line)
- The target line must contain a complete opening tag (not self-closing)
- PHP 8.0 or higher

