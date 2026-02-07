# Public API Reference

This document lists **only the public interfaces** of all classes. Implementation details are intentionally omitted.

---

## Namespace: `X4\Savegame`

### Class: `AncestorExtractor`

**Purpose**: Extracts the complete ancestor chain for an XML element at a specific line number.

#### Constructor
```php
public function __construct(string $filePath, int $targetLine)
```
**Parameters**:
- `$filePath` - Path to the XML file to parse
- `$targetLine` - Line number (1-based) of the target element

**Throws**: None (validation occurs in `extract()`)

#### Public Methods

```php
public function extract(): array
```
**Returns**: Array of ancestor elements, each containing:
- `name` (string) - Tag name
- `attributes` (array) - Associative array of attribute name => value
- `depth` (int) - Nesting depth (0-based, root is 0)
- `selfClosing` (bool) - Whether the element is self-closing

**Throws**: 
- `AncestorExtractionException` - If file doesn't exist, line is invalid, or target line doesn't contain a valid opening tag

**Example Return Value**:
```php
[
    ['name' => 'root', 'attributes' => [], 'depth' => 0, 'selfClosing' => false],
    ['name' => 'child', 'attributes' => ['id' => '123'], 'depth' => 1, 'selfClosing' => false],
]
```

#### Private State (Reference Only)
- `private string $filePath`
- `private int $targetLine`
- `private array $ancestorStack`
- `private int $currentLine`
- `private bool $targetFound`

---

### Class: `AncestorFormatter`

**Purpose**: Formats and writes ancestor chains to output files with proper indentation.

#### Constructor
```php
public function __construct()
```
**Parameters**: None

**Note**: This is a stateless service - can be reused for multiple format operations.

#### Public Constants
```php
private const INDENT_SPACES = 2;  // Number of spaces per indentation level
```

#### Public Methods

```php
public function format(array $ancestors, string $outputPath): void
```
**Parameters**:
- `$ancestors` - Array of ancestor elements (same format as `AncestorExtractor::extract()` returns)
- `$outputPath` - Path where the formatted output will be written

**Returns**: void

**Throws**: 
- `AncestorExtractionException` - If output directory cannot be created or file cannot be written

**Side Effects**:
- Creates output directory if it doesn't exist (with 0755 permissions)
- Writes formatted XML to file
- Overwrites existing file at `$outputPath`

**Output Format**:
- Each element on a new line
- Indentation: 2 spaces per depth level
- Attributes escaped using `htmlspecialchars()`
- Opening tags only (no closing tags)
- Trailing newline

---

## Namespace: `X4\Savegame\Exception`

### Class: `AncestorExtractionException`

**Purpose**: Domain-specific exception for all extraction and formatting errors.

#### Inheritance
```php
class AncestorExtractionException extends Exception
```

#### Constructor
```php
public function __construct(
    string $message = "", 
    int $code = 0, 
    ?Throwable $previous = null
)
```
**Note**: Inherits standard `Exception` constructor - no custom behavior.

#### Common Usage Patterns
```php
// File not found
throw new AncestorExtractionException("File not found: {$filePath}");

// Invalid line
throw new AncestorExtractionException("Line {$line} contains a self-closing tag, which is not allowed");

// Write failure
throw new AncestorExtractionException("Failed to write output file: {$outputPath}");
```

---

## Namespace: `Command`

### Class: `ExtractAncestorPath`

**Purpose**: Composer script entry point for the CLI tool.

#### Public Static Methods

```php
public static function main(Event $event): void
```
**Parameters**:
- `$event` - Composer script event containing arguments and I/O interface

**Returns**: void

**Side Effects**:
- Reads command-line arguments via `$event->getArguments()`
- Writes status messages to console via `$event->getIO()`
- Calls `exit(1)` on error
- Writes output file via `AncestorFormatter`

**Expected Arguments** (passed after `--`):
1. `xml-file` - Path to XML file
2. `line-number` - Target line number (positive integer)
3. `output-file` - Output path

**Example Usage**:
```bash
composer extract-ancestor-path -- saves/game.xml 1234 output/ancestors.xml
```

**Output Messages**:
- Progress: "Extracting ancestor chain from {file} at line {line}..."
- Success: "Found {count} ancestor(s) in the chain."
- Success: "Ancestor chain written to: {output}"
- Error: "Error: {message}"

**Exit Codes**:
- `0` - Success
- `1` - Error (invalid arguments, extraction failure, write failure)

---

## Type Definitions

### Ancestor Element Structure
```php
array{
    name: string,           // XML tag name
    attributes: array,      // Associative array of attribute => value
    depth: int,             // Nesting level (0 = root)
    selfClosing: bool       // Whether element is self-closing
}
```

### Example:
```php
[
    'name' => 'component',
    'attributes' => [
        'class' => 'station',
        'macro' => 'station_macro',
        'id' => '[0x123]'
    ],
    'depth' => 2,
    'selfClosing' => false
]
```

---

## Interface Contracts

### AncestorExtractor Contract
1. **Constructor** accepts file path and line number, but performs no validation
2. **extract()** performs all validation and parsing
3. **extract()** can be called only once per instance (not designed for reuse)
4. Throws `AncestorExtractionException` for ALL error cases (no other exception types)
5. Returns empty array if target is root element
6. Includes target element itself in the returned ancestor chain
7. Array is ordered from root (index 0) to target (last index)

### AncestorFormatter Contract
1. **format()** is idempotent - can be called multiple times with different inputs
2. Creates output directory automatically if needed
3. Overwrites existing output file without warning
4. Attributes are always escaped for XML safety
5. Output always ends with a newline character
6. Throws `AncestorExtractionException` for write failures

### ExtractAncestorPath Contract
1. Validates exactly 3 arguments (file, line, output)
2. Validates line number is positive integer
3. Exits with code 1 on any error
4. Provides user-friendly error messages (not raw exceptions)
5. Shows progress messages during execution
6. Never throws exceptions (catches and converts to exit codes)

---

## Dependency Graph

```
ExtractAncestorPath (Command)
    ↓ uses
    ├── AncestorExtractor (X4\Savegame)
    │       ↓ throws
    │       └── AncestorExtractionException (X4\Savegame\Exception)
    │
    └── AncestorFormatter (X4\Savegame)
            ↓ throws
            └── AncestorExtractionException (X4\Savegame\Exception)
```

**Note**: No circular dependencies. Clean unidirectional flow.

---

## Extension Points

### To Add New Output Formats
Create a new class implementing a format method:
```php
class JsonFormatter {
    public function format(array $ancestors, string $outputPath): void;
}
```

### To Add New Extraction Modes
Extend or create specialized extractors:
```php
class RangeExtractor {
    public function extractRange(int $startLine, int $endLine): array;
}
```

### To Add New Commands
Create static class in `Command\` namespace:
```php
class ExportCommand {
    public static function main(Event $event): void;
}
```

