# X4 Savegame

X4 savegame documentation and data files for XML structure reference.

## Bundled Savegames

- [X4 v8 - Scientist Start](saves/start-scientist-v8.xml.gz) - Game start - 369Mb XML
- [X4 v8 - Creative Advanced](saves/advanced-creative-v8.xml.gz) - 2 days play time - 704Mb XML

For each savegame, the extracted XML fragments (as sliced by the savegame parser) 
are included in a subfolder, in prettified XML format. This is a great resource to
view the structure of the XML.

> NOTE: To keep the project small, the XML files are zipped. 

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

#### Usage

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

