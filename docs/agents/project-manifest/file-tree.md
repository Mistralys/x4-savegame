# File Tree Structure

## Project Root
```
x4-savegame/
├── .git/                           # Git version control
├── .gitignore                      # Git ignore rules
├── .idea/                          # IDE configuration (JetBrains)
├── .phpunit.cache/                 # PHPUnit cache directory
│
├── composer.json                   # Dependency & script configuration
├── composer.lock                   # Locked dependency versions
├── phpunit.xml                     # PHPUnit configuration
├── package.json                    # Node.js metadata (minimal)
├── LICENSE                         # MIT License
├── README.md                       # User-facing documentation
│
├── docs/                           # Documentation
│   ├── player-locations.md         # Example usage documentation
│   └── agents/                     # AI agent documentation
│       ├── plans/                  # Implementation plans
│       │   └── ancestor-extraction-script.md
│       └── project-manifest/       # This manifest
│           ├── README.md
│           ├── tech-stack.md
│           ├── file-tree.md
│           ├── public-api.md
│           ├── data-flow.md
│           └── constraints.md
│
├── src/                            # Source code
│   ├── Command/                    # CLI command handlers
│   │   └── ExtractAncestorPath.php # Main CLI entry point
│   └── X4/
│       └── Savegame/               # Core business logic
│           ├── AncestorExtractor.php    # XML parsing & extraction
│           ├── AncestorFormatter.php    # Output formatting
│           └── Exception/               # Custom exceptions
│               └── AncestorExtractionException.php
│
├── tests/                          # Test suite
│   ├── fixtures/                   # Test data files
│   │   └── sample.xml              # Small XML for unit tests
│   ├── AncestorExtractorTest.php   # Extractor unit tests
│   └── AncestorFormatterTest.php   # Formatter unit tests
│
├── saves/                          # Real X4 savegame files (gitignored)
│   ├── *.xml.gz                    # Compressed savegames
│   └── advanced-creative-v8/       # Extracted savegame
│       ├── _xml-archive.7z
│       ├── savegame.info-001.xml
│       └── savegame.universe.*.xml # Large universe XML files
│
└── vendor/                         # Composer dependencies (gitignored)
    ├── autoload.php                # Composer autoloader
    ├── composer/                   # Composer internals
    └── phpunit/                    # PHPUnit framework
```

## Key Directories Explained

### `/src`
Contains all production source code. Organized by namespace:
- **Command/** - Entry points for Composer scripts
- **X4/Savegame/** - Core domain logic for savegame processing

### `/tests`
PHPUnit test suite with same structure as `/src`:
- **fixtures/** - Small, version-controlled test XML files
- Test classes mirror source classes by name

### `/docs`
All documentation:
- **agents/** - Documentation specifically for AI agents and planning
- **project-manifest/** - This manifest (source of truth for codebase understanding)

### `/saves`
Real X4 savegame files (large, not in git):
- Compressed `.xml.gz` files
- Extracted directories with individual XML components
- Used for integration testing and development

### `/vendor`
Composer-managed dependencies (gitignored):
- Auto-generated, not committed to version control
- Recreated via `composer install`

## File Naming Conventions

### Source Files
- `PascalCase.php` - One class per file
- Filename matches class name exactly
- Namespaces mirror directory structure

### Test Files
- `[ClassName]Test.php` - Unit test for corresponding class
- Located in `tests/` directory mirroring source structure

### Documentation Files
- `kebab-case.md` - Markdown documentation
- Descriptive names without numbers

### Configuration Files
- Lowercase with standard names: `composer.json`, `phpunit.xml`
- Dotfiles for tooling: `.gitignore`, `.phpunit.cache/`

## Important Files

### Configuration
- **composer.json** - Dependencies, autoloading, scripts
- **phpunit.xml** - Test suite configuration
- **.gitignore** - Excludes vendor/, caches, test outputs

### Entry Points
- **src/Command/ExtractAncestorPath.php** - Main CLI command
- **vendor/autoload.php** - Composer autoloader (bootstraps everything)

### Documentation
- **README.md** - User guide and usage examples
- **docs/agents/project-manifest/** - This manifest for AI agents

## Build Artifacts

### Generated Directories (gitignored)
- `.phpunit.cache/` - PHPUnit cache for faster test runs
- `vendor/` - Composer dependencies
- Test output files: `test-output*.xml`

### Version Controlled
- All source code in `src/`
- All tests in `tests/` (including fixtures)
- Documentation in `docs/`
- Configuration files at root

## Growth Patterns

### Adding New Features
1. Create class in `src/X4/Savegame/` or `src/Command/`
2. Create corresponding test in `tests/`
3. Update `composer.json` if adding new script
4. Document in `docs/` if user-facing

### Adding New Commands
1. Create static class in `src/Command/`
2. Add script entry in `composer.json`
3. Update README.md with usage

### Adding Test Fixtures
1. Add file to `tests/fixtures/`
2. Keep files small and focused
3. Document purpose in test comments

