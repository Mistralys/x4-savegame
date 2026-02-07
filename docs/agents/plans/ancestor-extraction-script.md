# Extract XML Ancestor Path with Composer Scripts and Organized Classes

**TL;DR:** Create extraction logic in `/src/X4/Savegame` classes, implement a static Composer script endpoint in `/src/Command`, add the script definition to [composer.json](composer.json), and document usage in [README.md](README.md).

## Steps

### 1. Create class structure under [src/X4/Savegame/](src/X4/Savegame/)

- `AncestorExtractor.php` — Core XMLReader logic: stream file, track lines, validate target element, build ancestor stack
- `AncestorFormatter.php` — Format extracted ancestors with proper indentation and write to file
- (Optional) `Exception/AncestorExtractionException.php` — Custom exceptions for error handling

### 2. Create [src/Command/ExtractAncestorPath.php](src/Command/ExtractAncestorPath.php)

- Static `main(Event $event)` method as Composer script entry point
- Extract and validate arguments via `$event->getArguments()`
- Instantiate and use `AncestorExtractor` and `AncestorFormatter` classes
- Output status/errors via `$event->getIO()`

### 3. Update [composer.json](composer.json)

- Configure PSR-4 autoload for `src/` directory
- Add `"scripts"` section with `"extract-ancestor-path": "Command\\ExtractAncestorPath::main"`

### 4. Update [README.md](README.md)

- Add "Tools" section with usage: `composer extract-ancestor-path -- <xml-file> <line-number> <output-file>`
- Provide example invocation and sample output (based on [player-locations.md](../player-locations.md))

## Requirements

### Input
- **XML file path** — Path to prettified, valid XML file (not compressed)
- **Line number** — Target line number (must point to a complete opening tag)
- **Output file path** — Path where ancestor chain will be written

### Output
- **Formatted ancestor chain** — XML elements with all attributes, indented with 2 spaces per depth level, from root to target element (inclusive)
- **Error messages** — Clear error output if validation fails

### Validation Rules
- Line number must be a positive integer
- Target line must contain a complete opening tag on a single line (no attribute wrapping)
- Reject self-closing tags (`/>`) as errors
- Accept root element in the ancestor chain
- Preserve all attributes from each ancestor element

### Example Output Format

For a player component deep in the XML hierarchy, the ancestor chain output should look like:

```xml
<connection connection="cluster_01_connection">
  <component class="cluster" macro="cluster_01_macro" connection="galaxy" code="DDZ-200" knownto="player" known="1" read="0" id="[0x1251]">
    <connections>
      <connection connection="c01s01_region002_connection">
        <component class="region" macro="c01s01_region002_macro" connection="cluster" id="[0x1253]">
          <connection connection="zone004_cluster_01_sector001_connection">
            <component class="zone" macro="zone004_cluster_01_sector001_macro" connection="sector" code="RII-277" knownto="player" id="[0x1313]">
              <connections>
                <connection connection="stations">
                  <component class="station" macro="station_pla_headquarters_base_01_macro" connection="space" code="PQC-446" owner="player" knownto="player" basename="{20102,2011}" level="0.2" variation="0" spawntime="0" modulevariation="0" id="[0x19759]">
                    <connections>
                      <connection connection="modules">
                        <component class="production" macro="landmarks_player_hq_01_research_macro" connection="space" name="{20104,101701}" construction="[0x142]" operationaltime="0" id="[0x1975b]">
                          <connections>
                            <connection connection="con_room_001">
                              <component class="navcontext" macro="virtual_navcontext_macro" connection="space" name="{20007,1171}" id="[0xa6703]">
                                <connections>
                                  <connection connection="rooms">
                                    <component class="room" macro="room_gen_boronoffice_01_macro" connection="space" seed="4009652551733623167" id="[0xa6705]">
                                      <connections>
                                        <connection connection="player">
                                          <component class="player" macro="character_player_scientist_macro" connection="room" name="{1021,802}" code="WXT-481" owner="player" known="1" read="0" id="[0x11e1c]">
```

## Composer Script Usage

Users invoke the script via:

```bash
composer extract-ancestor-path -- <xml-file> <line-number> <output-file>
```

**Example:**

```bash
composer extract-ancestor-path -- saves/start-scientist-v8.xml 12345 output/ancestor-chain.xml
```

## Technical Notes

- **XMLReader** is used for memory-efficient streaming of large XML files (300–700 MB)
- Line tracking is performed by counting newlines during XML stream parsing
- Ancestor stack is built during traversal to maintain parent-child relationships
- Output is formatted with consistent 2-space indentation matching the original file style

