# Project Manifest

**X4 Savegame Ancestor Extraction Tool**

This directory contains the comprehensive project manifest - the "Source of Truth" for understanding the codebase architecture without reading every line of implementation.

## Manifest Documents

### Core Documentation
- **[Tech Stack & Architecture](tech-stack.md)** - Runtime environment, libraries, and architectural patterns
- **[File Tree Structure](file-tree.md)** - Visual directory structure of the project
- **[Public API Reference](public-api.md)** - All public interfaces, method signatures, and contracts
- **[Data Flow](data-flow.md)** - How components interact and data moves through the system
- **[Constraints & Rules](constraints.md)** - Established patterns and requirements

## Quick Reference

### Purpose
Extract complete ancestor chains from specific XML elements in large X4 savegame files (600MB+) using line numbers.

### Entry Points
- **CLI Tool**: `composer extract-ancestor-path -- <xml-file> <line-number> <output-file>`
- **Testing**: `composer test`

### Core Components
1. **AncestorExtractor** - Streams XML using XMLReader, validates input, builds ancestor chain
2. **AncestorFormatter** - Formats and writes output with proper indentation
3. **ExtractAncestorPath** - Composer script command handler

### Technology
- **Runtime**: PHP 8.4+
- **Parser**: XMLReader (streaming for memory efficiency)
- **Testing**: PHPUnit 12.5+
- **Pattern**: Service-oriented with dependency injection

## For AI Agents

When working with this codebase:
1. Start with [Tech Stack & Architecture](tech-stack.md) to understand patterns
2. Review [Public API Reference](public-api.md) for component interfaces
3. Check [Constraints & Rules](constraints.md) for mandatory patterns
4. Refer to [Data Flow](data-flow.md) to understand interactions

**Last Updated**: 2026-02-07

