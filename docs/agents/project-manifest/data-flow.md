# Data Flow

This document describes how data moves through the system from user input to output file.

---

## High-Level Flow

```
User Command
    ↓
Composer Script Handler (ExtractAncestorPath)
    ↓
Input Validation
    ↓
Ancestor Extraction (AncestorExtractor)
    ↓
Ancestor Formatting (AncestorFormatter)
    ↓
Output File
```

---

## Detailed Flow

### 1. User Invocation

**Entry Point**: Command line via Composer
```bash
composer extract-ancestor-path -- saves/game.xml 1234 output/result.xml
```

**Data Flow**:
- Shell passes arguments to Composer
- Composer parses script definition from `composer.json`
- Composer invokes `Command\ExtractAncestorPath::main()` with Event object
- Event object contains:
  - Arguments array: `['saves/game.xml', '1234', 'output/result.xml']`
  - IO interface for console output

---

### 2. Command Handler (ExtractAncestorPath)

**Responsibilities**:
- Parse and validate command-line arguments
- Orchestrate extraction and formatting services
- Handle errors and provide user feedback

**Data Transformations**:

**Input**: 
```php
$event->getArguments() = ['saves/game.xml', '1234', 'output/result.xml']
```

**Validation**:
```php
// 1. Check argument count
if (count($args) !== 3) → exit(1)

// 2. Parse line number
$lineNumberStr = '1234'
$lineNumber = (int)$lineNumberStr  // 1234
```

**Orchestration**:
```php
// 3. Create extractor
$extractor = new AncestorExtractor('saves/game.xml', 1234)

// 4. Extract ancestors
$ancestors = $extractor->extract()
// Returns: array of ancestor elements

// 5. Format output
$formatter = new AncestorFormatter()
$formatter->format($ancestors, 'output/result.xml')
```

**Output**: Console messages via `$io->write()` and `$io->writeError()`

---

### 3. Ancestor Extraction (AncestorExtractor)

**Phase 1: Validation**

```php
// Input state
$this->filePath = 'saves/game.xml'
$this->targetLine = 1234

// Validation sequence
validateFile()
    ↓ Check file exists
    ↓ Check file is readable
    
validateLineNumber()
    ↓ Check line > 0
    
validateCompleteOpeningTag()
    ↓ Read file into lines array
    ↓ Check line exists
    ↓ Check line is not self-closing (no />)
    ↓ Check line matches pattern: ^<[^/][^>]*>$
```

**Phase 2: Dual-Pass Parsing**

```
Pass 1: Get Target Line Content
    ↓
file($filePath) → array of lines
    ↓
$targetLineContent = trim($lines[$targetLine - 1])
// Example: '<component class="station" id="[0x123]">'
```

```
Pass 2: Stream Parse with XMLReader
    ↓
XMLReader::open($filePath)
    ↓
while (read next node):
    if (ELEMENT node):
        ↓ Extract: name, attributes, depth
        ↓ Reconstruct tag string
        ↓ Compare with target line content
        ↓ Match? → return ancestor stack + current element
        ↓ No match? → push to stack if not self-closing
    
    if (END_ELEMENT node):
        ↓ Pop from stack
```

**Phase 3: Building Ancestor Stack**

```
Element Stack (during parsing):
[
    {name: 'root', attributes: {}, depth: 0},      // depth++ after push
    {name: 'level1', attributes: {...}, depth: 1}, // depth++ after push
    {name: 'level2', attributes: {...}, depth: 2}, // depth++ after push
]

When target found at depth 3:
    ↓
Ancestor Stack = Element Stack + Current Element
[
    {name: 'root', attributes: {}, depth: 0},
    {name: 'level1', attributes: {...}, depth: 1},
    {name: 'level2', attributes: {...}, depth: 2},
    {name: 'target', attributes: {...}, depth: 3},  // ← target element added
]
```

**Output**:
```php
return $this->ancestorStack;  // Array of ancestor elements
```

---

### 4. Ancestor Formatting (AncestorFormatter)

**Input**: Array of ancestor elements
```php
$ancestors = [
    ['name' => 'root', 'attributes' => [], 'depth' => 0],
    ['name' => 'child', 'attributes' => ['id' => '123'], 'depth' => 1],
]
```

**Transformation Pipeline**:

```
For each ancestor:
    ↓
1. Calculate indentation
   $indent = str_repeat(' ', $depth * 2)
   // depth=0 → ''
   // depth=1 → '  '
   // depth=2 → '    '
    ↓
2. Build opening tag
   $line = $indent . '<' . $name
    ↓
3. Append attributes
   foreach attribute:
       $line .= ' ' . $name . '="' . htmlspecialchars($value) . '"'
    ↓
4. Close tag
   $line .= '>'
    ↓
5. Add to lines array
   $lines[] = $line
```

**Example Transformation**:
```php
// Input element
['name' => 'component', 'attributes' => ['class' => 'station', 'id' => '[0x123]'], 'depth' => 1]

// Output line
'  <component class="station" id="[0x123]">'
```

**Output Assembly**:
```php
$output = implode("\n", $lines) . "\n"
// Joins all lines with newlines, adds trailing newline
```

**File Write**:
```php
// 1. Validate/create output directory
if (!is_dir(dirname($outputPath))):
    mkdir(dirname($outputPath), 0755, recursive: true)

// 2. Write file
file_put_contents($outputPath, $output)
```

---

## Data Structures

### Ancestor Element Structure

```php
[
    'name' => string,           // Tag name: 'component', 'connection', etc.
    'attributes' => [           // Key-value pairs
        'class' => 'station',
        'id' => '[0x123]',
        'macro' => 'station_macro'
    ],
    'depth' => int,             // 0-based nesting level
    'selfClosing' => bool       // true if <tag />, false if <tag>
]
```

### State Flow Through Components

```
Command Handler State:
- No persistent state
- Receives: Event with arguments
- Creates: Service instances
- Returns: void (side effect: console output, exit codes)

Extractor State:
- Persistent: $filePath, $targetLine, $ancestorStack
- Receives: Constructor parameters
- Creates: XMLReader, element stack
- Returns: Array of ancestors

Formatter State:
- No persistent state (stateless service)
- Receives: Ancestors array, output path
- Creates: Formatted string
- Returns: void (side effect: file write)
```

---

## Error Flow

### Exception Propagation

```
AncestorExtractor::extract()
    ↓ throws AncestorExtractionException
    ↑
ExtractAncestorPath::main()
    ↓ catches exception
    ↓ extracts message
    ↓ writes to $io->writeError()
    ↓ exit(1)
```

### Error Data Structure

```php
try {
    $extractor->extract()
} catch (AncestorExtractionException $e) {
    // $e->getMessage() contains human-readable error
    // Examples:
    // - "File not found: saves/game.xml"
    // - "Line 123 contains a self-closing tag, which is not allowed"
    // - "Line 999 is beyond the file length"
}
```

---

## Performance Characteristics

### Memory Usage

```
Input File: 600 MB XML
    ↓
Phase 1: Line Array
    Memory: ~600 MB (transient, released after validation)
    ↓
Phase 2: XMLReader Streaming
    Memory: ~constant (streaming parser)
    Element Stack: ~1 KB per 10 depth levels
    ↓
Output: Ancestor Array
    Memory: ~1 KB (only ancestor chain, not full tree)
```

**Peak Memory**: ~600 MB during line validation phase
**Steady State**: <10 MB during extraction

### Processing Flow

```
Time to Target Element:
    ↓
Sequential parsing: O(n) where n = elements before target
    ↓
Early termination when target found
    ↓
Average case: Much less than full file parse
```

---

## Interaction Patterns

### Services Don't Interact With Each Other

```
✓ ExtractAncestorPath → AncestorExtractor (orchestrates)
✓ ExtractAncestorPath → AncestorFormatter (orchestrates)
✗ AncestorExtractor ↔ AncestorFormatter (no direct communication)
```

**Pattern**: Command handler acts as orchestrator/facade

### Services Are Single-Use

```
$extractor = new AncestorExtractor($file, $line)
$result1 = $extractor->extract()  // ✓ Works
$result2 = $extractor->extract()  // ✗ Unexpected behavior (state not reset)
```

**Pattern**: Create new instance for each operation

### Formatters Are Reusable

```
$formatter = new AncestorFormatter()
$formatter->format($ancestors1, 'out1.xml')  // ✓ Works
$formatter->format($ancestors2, 'out2.xml')  // ✓ Also works
```

**Pattern**: Stateless services can be reused

---

## Testing Data Flow

### Unit Test Flow

```
Test Method
    ↓
Create service with test data
    ↓
Call public method
    ↓
Assert return value or side effects
```

**Example**:
```php
// Arrange
$extractor = new AncestorExtractor('tests/fixtures/sample.xml', 6)

// Act
$ancestors = $extractor->extract()

// Assert
$this->assertCount(5, $ancestors)
$this->assertEquals('root', $ancestors[0]['name'])
```

### Test Fixtures

```
tests/fixtures/sample.xml (small, fast)
    ↓ Used by unit tests
    ↓ Version controlled
    
saves/*.xml (large, realistic)
    ↓ Used by integration tests
    ↓ Skipped if not available
    ↓ Not in version control
```

