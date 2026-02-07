# Tech Stack & Architecture

## Runtime Environment

### PHP Version
- **Minimum**: PHP 8.4+
- **Type System**: Strict types enabled (`declare(strict_types=1)`)
- **Features Used**:
  - Constructor property promotion
  - Union types
  - Named arguments
  - Null coalescing operators
  - Arrow functions

### Required Extensions
- `ext-xmlreader` - For streaming XML parsing
- `ext-mbstring` - For string handling

## Dependencies

### Production Dependencies
None - The tool is standalone with no runtime dependencies outside of PHP core extensions.

### Development Dependencies
- **PHPUnit 12.5+** - Unit testing framework
- **Composer 2.x** - Dependency management and script runner

## Architectural Patterns

### 1. Service-Oriented Architecture
- Each core functionality is encapsulated in a dedicated service class
- Services are stateful but designed for single-use per instance
- Clear separation of concerns:
  - **Extraction** (AncestorExtractor)
  - **Formatting** (AncestorFormatter)
  - **CLI Handling** (ExtractAncestorPath)

### 2. Exception-Based Error Handling
- Custom exception hierarchy for domain-specific errors
- All validation failures throw `AncestorExtractionException`
- Exceptions are caught at the CLI boundary and converted to user-friendly messages

### 3. Streaming Parser Pattern
- Uses XMLReader for memory-efficient parsing of large files (600MB+)
- Maintains internal state stack during traversal
- Early termination when target element is found

### 4. Hybrid Parsing Strategy
- **First pass**: Read file line-by-line to get target line content
- **Second pass**: Stream parse with XMLReader to build ancestor stack
- **Matching**: Reconstruct tags from DOM and compare with source line

### 5. Validation Chain Pattern
- Multiple validation methods called in sequence
- Each validator is responsible for one concern
- Fail-fast approach - first violation throws exception

### 6. Static Entry Point Pattern
- Composer scripts call static methods
- Static method acts as composition root
- Instantiates services and orchestrates workflow

## Code Organization

### Namespace Structure
```
X4\Savegame\           - Core extraction and formatting logic
  Exception\           - Custom exception classes
Command\               - CLI command handlers (Composer scripts)
```

### PSR-4 Autoloading
- `X4\Savegame\` → `src/X4/Savegame/`
- `Command\` → `src/Command/`

## Design Decisions

### Why XMLReader over DOMDocument?
- **Memory efficiency**: DOMDocument loads entire XML into memory (impractical for 600MB files)
- **Streaming**: XMLReader processes XML incrementally
- **Performance**: Only parses until target is found

### Why Dual-Pass Parsing?
- **Accuracy**: Source line content is canonical - preserves exact formatting
- **Validation**: Can validate line structure before expensive XML parsing
- **Flexibility**: Doesn't rely on XMLReader line number tracking (unreliable with whitespace)

### Why Private Methods?
- All internal logic is private to enforce single public API surface
- Easier to refactor internals without breaking consumers
- Clear contract: public methods are the API, private are implementation

### Why No Interfaces?
- Current implementation has single concrete implementation per service
- YAGNI principle - no need for polymorphism yet
- Can extract interfaces later if needed for testing or extensibility

## Testing Strategy

### Test Organization
- Unit tests mirror source structure: `tests/` mirrors `src/`
- Test fixtures in `tests/fixtures/` directory
- Separate test classes per service class

### Testing Approach
- **Unit tests**: Test public methods of each service in isolation
- **Integration tests**: Test with real savegame files (skipped if unavailable)
- **Edge cases**: Comprehensive coverage of validation failures
- **Test doubles**: Minimal mocking - prefer real file fixtures

### Test Naming Convention
- `test[MethodName][Scenario]()` - e.g., `testExtractNestedElement()`
- Descriptive scenario names
- One assertion concept per test

## Build & Development

### Composer Scripts
- `composer extract-ancestor-path` - Run the CLI tool
- `composer test` - Run PHPUnit test suite

### Configuration Files
- `composer.json` - Dependencies and scripts
- `phpunit.xml` - PHPUnit configuration
- `.gitignore` - Exclude vendor, cache, test outputs

## Performance Characteristics

### Time Complexity
- **Best case**: O(d) where d is depth to target element
- **Worst case**: O(n) where n is total elements (if target not found)
- **Average**: Early termination makes it much faster than O(n)

### Space Complexity
- **Stack**: O(d) where d is maximum nesting depth
- **File reading**: O(1) streaming with XMLReader
- **Line cache**: O(n) for line-by-line array (acceptable for validation)

### Scalability
- Tested with 600MB+ XML files
- Memory usage remains constant regardless of file size (thanks to XMLReader)
- Performance degradation is linear with depth, not file size

## Future Extensibility Points

### Potential Enhancements
1. **Output formats**: Add JSON, YAML formatters (implement Formatter interface)
2. **Multiple targets**: Extract multiple line numbers in one pass
3. **Caching**: Cache parsed structure for repeated queries
4. **Path queries**: Find elements by XPath instead of line number
5. **Parallel processing**: Process multiple files concurrently

### Where to Add Features
- **New output formats**: Create new formatter classes implementing common interface
- **New extraction modes**: Extend AncestorExtractor or create specialized subclasses
- **New commands**: Add static methods to Command\ namespace classes

