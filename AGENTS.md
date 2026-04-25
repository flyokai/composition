# flyokai/composition

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

Module ordering via topological sort using `marcj/topsort`.

## Key Abstractions

### Functions (auto-loaded via composer)
- `castCompositionArgument(array $items, string $name, string $classNameKey)` — sorts items and returns ordered `class-string[]`
- `sortComposition(array $items)` — core sorting: processes `before`/`after`/`depends` relationships, returns sorted array keyed by item name
- `assertCompositionKeys(array $items, array $keys, string $name)` — validates all items contain required keys after sorting
- `mergeDepends(array $depends, array $merge)` — utility to merge and deduplicate dependency arrays

### ArraySortGroup
`TopSort\ArraySortGroup` extends `MJS\TopSort\Implementations\ArraySort`:
- Adds grouping capability for parallel execution
- `sorted()` — lazy-evaluated cached sorted list
- `grouped()` — elements grouped by dependency sets (items in same group have no cross-dependencies)
- `add()`/`set()` invalidate cache and reset visited flags

### Dependency Declaration Format
```php
[
    'itemName' => [
        'className' => 'Full\Class\Name',
        'before' => 'item2,item3',    // string or array
        'after' => 'item1',           // string or array
        'depends' => 'other1,other2'  // string or array
    ]
]
```

| Mechanism | Semantics |
|-----------|-----------|
| `before` | "This item comes before X" → X depends on this item (REVERSE) |
| `after` | "This item comes after X" → this item depends on X |
| `depends` | Direct dependency declaration |

## Gotchas

- **`before` is inverted**: `'before' => 'cache'` means cache DEPENDS ON this item, not the other way
- **Missing dependencies throw**: `ElementNotFoundException` — no silent failures
- **Circular dependencies detected**: `CircularDependencyException` via parent tracking
- **Adding elements invalidates sort**: Full re-sort required after any `add()`
- **Flexible input**: Accepts string (`'a,b'`), array (`['a','b']`), or empty string (filtered out)
