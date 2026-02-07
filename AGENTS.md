# Agents Guide

## 📚 Project Manifest - Start Here!

**Before exploring the codebase**, consult the **Project Manifest** - the authoritative source of truth for understanding this application's architecture, patterns, and constraints.

### 🎯 Location
```
/docs/agents/project-manifest/
```

### 📖 Manifest Documents

The manifest contains everything you need without reading implementation code:

1. **[README.md](docs/agents/project-manifest/README.md)** - Start here for overview and navigation
2. **[tech-stack.md](docs/agents/project-manifest/tech-stack.md)** - Runtime, libraries, architectural patterns
3. **[file-tree.md](docs/agents/project-manifest/file-tree.md)** - Complete directory structure
4. **[public-api.md](docs/agents/project-manifest/public-api.md)** - All class signatures and contracts
5. **[data-flow.md](docs/agents/project-manifest/data-flow.md)** - How components interact
6. **[constraints.md](docs/agents/project-manifest/constraints.md)** - **CRITICAL** - Mandatory patterns and rules

### 🚀 Quick Start Workflow for Agents

```
1. Read manifest/README.md (overview + quick reference)
   ↓
2. Read manifest/tech-stack.md (understand patterns)
   ↓
3. Read manifest/constraints.md (learn the rules - MUST FOLLOW)
   ↓
4. Use manifest/public-api.md (reference during implementation)
   ↓
5. Only read source files if implementation details are needed
```

### ⚠️ Important Rules

- **ALWAYS check `constraints.md` before making changes** - it contains mandatory patterns
- **Use public-api.md for signatures** - don't infer from source code
- **Follow data-flow.md patterns** - don't introduce new interaction patterns
- **The manifest is the source of truth** - if manifest conflicts with code, code may be wrong

### 📝 Manifest Maintenance Rules

**You MUST update the manifest when making structural changes:**

| Change Made | Documents to Update |
|-------------|---------------------|
| Add/remove service class | `public-api.md`, `file-tree.md`, `data-flow.md` |
| Add new architectural pattern | `tech-stack.md`, `constraints.md` |
| Change data flow between components | `data-flow.md` |
| Add new constraint/rule | `constraints.md` |
| Add/remove directories | `file-tree.md` |
| Change dependencies | `tech-stack.md` |
| Add new command/script | `public-api.md`, README in manifest |

**Update Process:**
1. Make code changes
2. Update relevant manifest documents
3. Verify no contradictions across manifest files
4. Update timestamp in manifest/README.md

**Never skip manifest updates** - outdated manifest is worse than no manifest.

### 🔍 When to Use Each Document

| Task | Document to Consult |
|------|---------------------|
| Understanding project purpose | `README.md` |
| Adding new features | `tech-stack.md` + `constraints.md` |
| Finding a class/file | `file-tree.md` ⚡ **Search here FIRST** |
| Calling a method | `public-api.md` |
| Understanding interactions | `data-flow.md` |
| Validating changes | `constraints.md` |
| Error handling patterns | `constraints.md` + `data-flow.md` |

### ⚡ Efficiency Rules - Search Smart

**ALWAYS use manifest files before searching the codebase:**

1. **Finding files/classes?** → Read `file-tree.md` FIRST
   - It's faster than file system searches
   - Keeps you within the authorized mental model
   - Prevents exploring irrelevant directories

2. **Understanding a method?** → Check `public-api.md` FIRST
   - Don't read source code to infer signatures
   - API reference is the contract, code is implementation

3. **Need implementation details?** → Only THEN read source files
   - Use manifest to locate the file first
   - Only read the specific class/method you need

**Example Workflow:**
```
❌ Wrong: "Let me search for AncestorExtractor..."
✅ Right: "file-tree.md shows AncestorExtractor is at src/X4/Savegame/AncestorExtractor.php"

❌ Wrong: "Let me read the code to see what extract() returns..."
✅ Right: "public-api.md shows extract() returns array of ancestor elements"
```

### 💡 Key Constraints Summary

Must follow these rules (see `constraints.md` for complete list):

- ✅ Use `XMLReader` for parsing (not DOMDocument)
- ✅ All exceptions must be `AncestorExtractionException`
- ✅ Strict types: `declare(strict_types=1)`
- ✅ Services are single-use (create new instance per operation)
- ✅ No service-to-service dependencies
- ✅ Public methods only for API surface
- ✅ Test all public methods with success and failure cases

### 🚨 Failure Protocol - Handling Edge Cases

**When you encounter a situation not covered by the manifest:**

#### 1. Pattern/Style Not Defined
```
If constraints.md doesn't define a pattern:
  → Default to PSR-12 coding standards
  → Document your decision in code comments
  → Flag for human review in your response
```

**Example:**
```php
// Pattern not defined in constraints.md - following PSR-12
// TODO: Add to constraints.md if this becomes a repeated pattern
public function newMethod(): void { ... }
```

#### 2. Conflicting Information
```
If manifest contradicts source code:
  → Trust the manifest first
  → Investigate the discrepancy
  → Fix the code OR update the manifest
  → Never assume code is correct by default
```

#### 3. Missing API Documentation
```
If public-api.md is missing a public method:
  → STOP: This is a critical error
  → Update public-api.md FIRST
  → Then proceed with your task
```

#### 4. Ambiguous Requirements
```
If requirements are unclear:
  → Check data-flow.md for context
  → Look for similar patterns in existing code
  → Default to most restrictive interpretation (fail-safe)
  → Document assumptions clearly
```

#### 5. Test Coverage Gaps
```
If you find untested public methods:
  → Add tests BEFORE making changes
  → Follow existing test patterns
  → Cover both success and failure cases
```

### 🎯 Decision Matrix

| Scenario | Action | Priority |
|----------|--------|----------|
| Pattern defined in constraints.md | Follow it exactly | MUST |
| Pattern not in constraints.md | Use PSR-12 + flag for review | SHOULD |
| Manifest vs code conflict | Trust manifest, fix code | MUST |
| Missing API documentation | Update manifest first | MUST |
| Ambiguous requirement | Use most restrictive option | SHOULD |
| No test coverage | Add tests before changes | MUST |

### 📊 Project Stats

- **Language**: PHP 8.4+
- **Pattern**: Service-oriented architecture
- **Testing**: PHPUnit 12.5+ (17 tests, 54 assertions)
- **Lines**: ~1,200 lines of documentation in manifest

### 🎯 CLI Entry Points

```bash
# Run the tool
composer extract-ancestor-path -- <xml-file> <line-number> <output-file>

# Run tests
composer test
```

---

## Additional Resources

- `/docs/player-locations.md` - Example usage
- `/docs/agents/plans/` - Implementation plans and design documents

