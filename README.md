# flyokai/composition

> User docs → [`README.md`](README.md) · Agent quick-ref → [`CLAUDE.md`](CLAUDE.md) · Agent deep dive → [`AGENTS.md`](AGENTS.md)

> Topological-sort helpers for declaring `before`, `after`, and `depends` relationships between modules and pipeline items.

A thin layer on top of [`marcj/topsort`](https://github.com/marcj/topsort.php) that gives Flyokai a single, consistent way to order things — modules at bootstrap, ACL tuners, indexers, setup steps, anywhere a list needs to be deterministic.

## Features

- `sortComposition(array $items)` — sorts items by `before`/`after`/`depends` declarations
- `castCompositionArgument(array $items, string $name, string $key)` — sort + validate, returns ordered class names
- `assertCompositionKeys()` — ensures every item has the keys it must
- `mergeDepends()` — merge & dedupe dependency arrays
- `ArraySortGroup` — extends `MJS\TopSort\Implementations\ArraySort` with grouping for parallel-safe execution

## Installation

```bash
composer require flyokai/composition
```

## Quick start

```php
use function Flyokai\Composition\sortComposition;

$items = [
    'cache'  => ['className' => Cache::class,  'after' => 'config'],
    'config' => ['className' => Config::class],
    'db'     => ['className' => Db::class,     'depends' => 'config'],
    'http'   => ['className' => Http::class,   'before' => 'db'],
];

$sorted = sortComposition($items);

// Returns: ['http', 'config', 'cache', 'db'] — keys preserved, order corrected
```

## Item shape

```php
[
    'itemName' => [
        'className' => 'Full\\Class\\Name',
        'before'    => 'item2,item3',     // string or array
        'after'     => 'item1',           // string or array
        'depends'   => 'other1,other2',   // string or array
    ]
]
```

| Mechanism | Semantics |
|-----------|-----------|
| `before`  | "this item comes before X" → **X depends on this item** |
| `after`   | "this item comes after X" → this item depends on X |
| `depends` | direct dependency declaration |

`before` is intentionally inverted — `'before' => 'cache'` means *cache* depends on the current item, not the other way.

## Casting to ordered class names

```php
use function Flyokai\Composition\castCompositionArgument;

$ordered = castCompositionArgument($items, 'modules', 'className');
// ['Http\\Class', 'Config\\Class', 'Cache\\Class', 'Db\\Class']
```

`assertCompositionKeys($items, ['className', 'tag'], 'pipelines')` enforces that every entry contains the listed keys after sorting.

## Group sorting

```php
use Flyokai\Composition\TopSort\ArraySortGroup;

$sorter = new ArraySortGroup();
$sorter->add('a', []);
$sorter->add('b', ['a']);
$sorter->add('c', []);
$sorter->add('d', ['b']);

$sorter->sorted();  // ['a', 'c', 'b', 'd']
$sorter->grouped(); // [['a', 'c'], ['b'], ['d']] — items in a group are independent
```

`grouped()` returns dependency cohorts — every item in a cohort can run in parallel without violating ordering.

## Gotchas

- **`before` is inverted.** `'before' => 'cache'` declares *cache* as depending on this item.
- **Missing dependencies throw** — `ElementNotFoundException`. No silent omission.
- **Cycles throw** — `CircularDependencyException` via parent tracking.
- Adding elements after sorting invalidates the cached result; a full re-sort runs on next access.
- Accepts `'a,b'`, `['a','b']`, or empty string interchangeably (empties are filtered out).

## See also

- [`flyokai/application`](../application/README.md) uses this for module ordering and DB setup steps.
- [amphp-injector](../amphp-injector/README.md) ships its own `CompositionOrdered` for ordered DI compositions, built on the same primitives.

## License

MIT
