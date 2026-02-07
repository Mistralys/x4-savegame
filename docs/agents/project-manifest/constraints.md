# Constraints & Rules

This document lists all established patterns, requirements, and constraints that must be followed when working with this codebase.

---

## Code Style & Standards

### PHP Standards

#### ✓ MUST: Strict Types
```php
declare(strict_types=1);
```
**Reason**: Prevents type coercion bugs, enforces type safety.
**Location**: First line after opening `<?php` tag in every file.

#### ✓ MUST: PHP 8.4+ Features
- Type declarations on all parameters and return types
- Constructor property promotion where applicable
- Null coalescing operators (`??`, `??=`)
- Arrow functions for simple callbacks

**Reason**: Modern PHP features improve code safety and readability.

#### ✓ MUST: PSR-12 Code Style
- 4 spaces for indentation (not tabs)
- Opening braces on same line for control structures
- Opening braces on new line for classes and methods
- One blank line after namespace declaration

#### ✓ MUST: One Class Per File
- File name must match class name exactly
- Namespace must match directory structure

---

## Architecture Constraints

### Service Design

#### ✓ MUST: Public API Only
```php
// ✓ Correct - only public methods are the API
class MyService {
    public function doSomething(): void { ... }
    private function helper(): void { ... }  // Implementation detail
}

// ✗ Wrong - exposing implementation details
class MyService {
    public function doSomething(): void { ... }
    public function helper(): void { ... }  // Should be private
}
```

**Reason**: Clear separation between API contract and implementation. Internal methods can be refactored freely.

#### ✓ MUST: Single Responsibility
Each class should have one clear responsibility:
- **AncestorExtractor**: Parse and extract ancestors
- **AncestorFormatter**: Format and write output
- **ExtractAncestorPath**: Handle CLI interaction

**Reason**: Easier to test, maintain, and reason about.

#### ✓ MUST: No Service-to-Service Dependencies
```php
// ✓ Correct - command orchestrates services
class ExtractAncestorPath {
    public static function main(Event $event): void {
        $extractor = new AncestorExtractor(...);
        $formatter = new AncestorFormatter();
    }
}

// ✗ Wrong - services shouldn't depend on each other
class AncestorFormatter {
    private AncestorExtractor $extractor;  // ✗ No!
}
```

**Reason**: Prevents tight coupling, maintains testability.

---

## File I/O Constraints

### ✓ MUST: Use Streaming for Large Files
```php
// ✓ Correct - XMLReader streams the file
$reader = new XMLReader();
$reader->open($filePath);

// ✗ Wrong - loads entire file into memory
$dom = new DOMDocument();
$dom->load($filePath);  // Don't use for 600MB files!
```

**Reason**: X4 savegame files can be 600MB+. DOMDocument would exhaust memory.

### ✓ MUST: Synchronous File Operations
All file I/O is synchronous (blocking):
- `file()` - Read file into array
- `file_put_contents()` - Write to file
- `XMLReader::open()` - Open for streaming

**Reason**: PHP's native file functions are synchronous. Async would require additional dependencies (ReactPHP, Amp).

**Note**: This is acceptable for a CLI tool. If building a web service, this would need to change.

### ✓ MUST: Create Directories Automatically
```php
// Always create parent directories
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);  // recursive: true
}
```

**Reason**: User convenience - don't require manual directory creation.

---

## Error Handling

### ✓ MUST: Use Domain Exceptions
```php
// ✓ Correct
throw new AncestorExtractionException("File not found: {$filePath}");

// ✗ Wrong
throw new RuntimeException("File not found: {$filePath}");
throw new Exception("File not found: {$filePath}");
```

**Reason**: Domain exceptions can be caught specifically and provide semantic meaning.

### ✓ MUST: Fail Fast
```php
// ✓ Correct - validate before processing
public function extract(): array {
    $this->validateFile();
    $this->validateLineNumber();
    $this->validateCompleteOpeningTag();
    // ... then process
}

// ✗ Wrong - discover error late in processing
public function extract(): array {
    // ... complex processing
    if (!file_exists($this->filePath)) {  // Too late!
        throw new Exception();
    }
}
```

**Reason**: Fail early with clear error messages. Don't waste time processing if preconditions aren't met.

### ✓ MUST: User-Friendly Error Messages
```php
// ✓ Correct - descriptive and actionable
"Line 123 contains a self-closing tag, which is not allowed"

// ✗ Wrong - technical jargon
"Invalid node type at offset 12345"
```

**Reason**: This is a user-facing tool. Errors should guide users to fix the problem.

### ✓ MUST: Convert Exceptions at Boundaries
```php
// ✓ Correct - CLI boundary catches and converts
public static function main(Event $event): void {
    try {
        $extractor->extract();
    } catch (AncestorExtractionException $e) {
        $io->writeError("<error>Error: {$e->getMessage()}</error>");
        exit(1);  // Don't let exception propagate to user
    }
}
```

**Reason**: Users shouldn't see stack traces. Convert to exit codes and messages.

---

## Testing Requirements

### ✓ MUST: Test All Public Methods
Every public method must have at least one test:
```php
// AncestorExtractor::extract() tested by:
- testExtractRootElement()
- testExtractNestedElement()
- testRejectSelfClosingTag()
// ... etc
```

### ✓ MUST: Test Error Cases
For every validation, test both success and failure:
```php
// Valid input
testExtractNestedElement()  // ✓ Should succeed

// Invalid inputs
testRejectSelfClosingTag()         // ✗ Should throw
testRejectLineNumberBeyondFile()   // ✗ Should throw
testRejectInvalidLineNumber()      // ✗ Should throw
```

### ✓ MUST: Use Fixtures for File Tests
```php
// ✓ Correct - use version-controlled fixture
$this->fixtureDir = __DIR__ . '/fixtures';
$extractor = new AncestorExtractor($this->fixtureDir . '/sample.xml', 6);

// ✗ Wrong - don't rely on external files
$extractor = new AncestorExtractor('C:/Users/Me/game.xml', 6);
```

**Reason**: Tests must be reproducible in any environment.

### ✓ MUST: Clean Up Test Artifacts
```php
protected function tearDown(): void {
    // Delete any files created during test
    $this->deleteDirectory($this->tempDir);
}
```

**Reason**: Tests shouldn't pollute the file system.

### ✓ SHOULD: Skip Integration Tests If Resources Missing
```php
if (!file_exists($savegamePath)) {
    $this->markTestSkipped('Real savegame file not available');
}
```

**Reason**: Not everyone has large savegame files. Unit tests should always run.

---

## Validation Rules

### XML Structure Requirements

#### ✓ MUST: Validate Complete Opening Tags
```php
// ✓ Valid targets
<component class="station">
<root>
<element attr1="value1" attr2="value2">

// ✗ Invalid targets (must throw exception)
<element />                    // Self-closing
</element>                     // Closing tag
<element                       // Incomplete (multi-line)
  attr="value">
```

**Reason**: Tool is designed for single-line tags. Multi-line tags complicate line-number-based targeting.

#### ✓ MUST: Reject Self-Closing Tags
```php
if (str_ends_with($line, '/>')) {
    throw new AncestorExtractionException(
        "Line {$lineNumber} contains a self-closing tag, which is not allowed"
    );
}
```

**Reason**: Self-closing tags have no descendants, so ancestor chain would be incomplete/misleading.

#### ✓ MUST: Validate Line Number Range
```php
// Must be positive
if ($lineNumber < 1) {
    throw new AncestorExtractionException("Line number must be a positive integer");
}

// Must exist in file
if (!isset($lines[$lineNumber - 1])) {
    throw new AncestorExtractionException("Line {$lineNumber} is beyond the file length");
}
```

---

## Output Format Constraints

### ✓ MUST: Use 2-Space Indentation
```php
private const INDENT_SPACES = 2;
```

**Reason**: Consistent with common XML formatting conventions. Readable but not wasteful.

### ✓ MUST: Include All Attributes
```php
// Don't filter or omit any attributes
foreach ($ancestor['attributes'] as $name => $value) {
    $line .= ' ' . $name . '="' . $value . '"';
}
```

**Reason**: Attributes often contain critical information (IDs, references). User needs complete context.

### ✓ MUST: Escape Attribute Values
```php
htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
```

**Reason**: Attribute values may contain special XML characters that need escaping.

### ✓ MUST: Output Opening Tags Only
```php
// ✓ Correct
<root>
  <child>

// ✗ Wrong - don't include closing tags
<root>
  <child>
  </child>
</root>
```

**Reason**: Ancestor chain shows the path, not the full document structure. Closing tags add noise.

### ✓ MUST: End with Newline
```php
return implode("\n", $lines) . "\n";
```

**Reason**: POSIX standard - text files should end with newline.

---

## Dependency Constraints

### ✓ MUST: Minimize Dependencies
**Production**: Zero dependencies (except PHP extensions)
**Development**: PHPUnit only

**Reason**: Tool should be lightweight and easy to install.

### ✓ MUST: Declare Extension Requirements
```php
// In code
use XMLReader;  // ext-xmlreader required

// In composer.json (as warning, not hard requirement)
"suggest": {
    "ext-xmlreader": "Required for XML parsing"
}
```

**Reason**: Make requirements explicit, but don't enforce (extensions usually bundled with PHP).

---

## Performance Guidelines

### ✓ SHOULD: Early Termination
```php
while ($reader->read()) {
    if (targetFound) {
        return;  // Don't continue parsing
    }
}
```

**Reason**: Once target is found, no need to parse the rest of the file.

### ✓ SHOULD: Use isset() for Array Checks
```php
// ✓ Fast
if (isset($array[$key])) { ... }

// ✗ Slower
if (array_key_exists($key, $array)) { ... }
```

**Reason**: `isset()` is a language construct (faster than function call) and handles the common case efficiently.

---

## Documentation Requirements

### ✓ MUST: Document Public APIs
```php
/**
 * Extract the ancestor chain for the element at the target line.
 *
 * @return array Array of ancestor elements with their tag names, attributes, and depth
 * @throws AncestorExtractionException
 */
public function extract(): array
```

**Required Elements**:
- Purpose summary
- `@param` for each parameter
- `@return` with type and description
- `@throws` for each exception type

### ✓ SHOULD: Document Complex Logic
```php
// Reconstruct the opening tag to match against target line
// This is necessary because XMLReader normalizes the tag format
$tag = '<' . $element['name'] . '...';
```

**When**: If the "why" isn't obvious from the code.

### ✓ MUST: Keep README Updated
When adding features:
1. Update usage examples
2. Update requirements
3. Update examples in README

---

## Git & Version Control

### ✓ MUST: Ignore Generated Files
```gitignore
/vendor/
/.phpunit.cache/
/test-output*.xml
composer.lock  # For libraries (not applications)
```

### ✓ MUST: Include Test Fixtures
```gitignore
# ✓ Version control small test fixtures
tests/fixtures/sample.xml

# ✗ Don't version control large files
saves/*.xml
```

---

## Breaking Changes

### These Rules Cannot Be Changed Without Major Version Bump:

1. **Return type of `AncestorExtractor::extract()`** - consumers depend on array structure
2. **Exception types thrown** - consumers catch specific exception types
3. **Output format** - users/tools parse the output
4. **Command-line argument order** - would break scripts
5. **PHP version requirement** - would break environments

### These Rules Can Be Changed in Minor Versions:

1. Internal implementation details
2. Private method signatures
3. Performance optimizations
4. Additional validation (if backward compatible)
5. New optional features

---

## Summary Checklist

When adding new code, verify:

- [ ] Strict types declared
- [ ] All parameters and returns have type hints
- [ ] Public methods documented with PHPDoc
- [ ] Error cases throw `AncestorExtractionException`
- [ ] User-facing errors have clear messages
- [ ] File I/O uses streaming for large files
- [ ] Tests cover success and failure cases
- [ ] Test fixtures are small and version controlled
- [ ] No service-to-service dependencies
- [ ] README updated if user-facing changes

