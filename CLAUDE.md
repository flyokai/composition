# flyokai/composition

Module ordering via topological sort. Provides `sortComposition()` and `castCompositionArgument()` functions for dependency-based ordering with `before`/`after`/`depends` declarations.

See [AGENTS.md](AGENTS.md) for detailed module knowledge.

## Quick Reference

- **Core function**: `sortComposition(array $items)` — sorts by before/after/depends
- **Public API**: `castCompositionArgument($items)` — sort + validate + return class names
- **Dependency lib**: `marcj/topsort` ^2
- **Key gotcha**: `before` is inverted — `'before' => 'X'` means X depends on this item
