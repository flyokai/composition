<?php

namespace Flyokai\Composition;

use MJS\TopSort\Implementations\ArraySort;

/**
 * @param array<string, array{
 *      className: class-string,
 *      before?: array|string,
 *      after?: array|string,
 *      depends?: array|string
 *  }> $compositionItems
 * @param string $compositionName
 * @return class-string[]
 * @throws \InvalidArgumentException
 */
function castCompositionArgument(
    array $compositionItems, string $compositionName = 'items', string $classNameKey = 'className'
): array
{
    $compositionItems = sortComposition($compositionItems);
    assertCompositionKeys($compositionItems, [$classNameKey], $compositionName);
    return array_map(fn($item) => $item[$classNameKey], $compositionItems);
}
/**
 * @param array<string, array> $compositionItems
 */
function assertCompositionKeys(array $compositionItems, array $keys, string $compositionName = 'items'): void
{
    $result = sortComposition($compositionItems);
    foreach ($result as $name=>$item) {
        foreach ($keys as $key) {
            if (!($item[$key]??false)) {
                throw new \InvalidArgumentException(sprintf(
                    'Missing %s in %s %s argument',
                    $key, $name, $compositionName
                ));
            }
        }
    }
}

function sortComposition(array $compositionItems): array
{
    $sorter = new ArraySort;
    $sortKeys = ['before', 'after', 'depends'];
    foreach ($compositionItems as &$item) {
        foreach ($sortKeys as $sortKey) {
            $item[$sortKey] ??= '';
            if (!is_array($item[$sortKey])) {
                $item[$sortKey] = explode(',', $item[$sortKey]);
            }
        }
    }
    unset($item);
    foreach ($compositionItems as $name => $item) {
        foreach ($item['before']??[] as $before) {
            if (isset($compositionItems[$before])) {
                $compositionItems[$before]['depends'] = mergeDepends($compositionItems[$before]['depends'],
                    [$name]);
            }
        }
        $compositionItems[$name]['depends'] = mergeDepends(
            $compositionItems[$name]['depends']??[],
            $item['after']??[]
        );
    }
    foreach ($compositionItems as $name=> $item) {
        $sorter->add($name, $item['depends']);
    }
    $sortedNames = $sorter->sort();
    $result = [];
    foreach ($sortedNames as $name) {
        $result[$name] = $compositionItems[$name];
    }
    return $result;
}

function mergeDepends(array $depends, array $merge): array
{
    return array_filter(array_unique(array_merge($depends, $merge)));
}
