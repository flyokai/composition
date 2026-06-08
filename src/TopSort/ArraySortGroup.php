<?php

namespace Flyokai\Composition\TopSort;

use MJS\TopSort\ElementNotFoundException;

class ArraySortGroup extends \MJS\TopSort\Implementations\ArraySort
{
    protected $parents;
    protected $grouped;
    protected function visit($element, &$parents = null)
    {
        $this->throwCircularExceptionIfNeeded($element, $parents);

        // If element has not been visited
        if (!$element->visited) {
            $parents[$element->id] = true;

            // Set that element has been visited
            $element->visited = true;

            foreach ($element->dependencies as $dependency) {
                if (isset($this->elements[$dependency])) {
                    $newParents = $parents;
                    $this->visit($this->elements[$dependency], $newParents);
                } else {
                    throw ElementNotFoundException::create($element->id, $dependency);
                }
            }

            $this->parents[$element->id] = array_filter(
                array_unique(array_merge($parents, $element->dependencies)),
                fn ($__k) => $__k != $element->id,
                ARRAY_FILTER_USE_KEY
            );
            $this->addToList($element);
        }
    }

    public function add($element, $dependencies = array())
    {
        $this->sortedFlag = false;
        $this->reset();
        parent::add($element, $dependencies);
    }

    public function set(array $elements)
    {
        $this->sortedFlag = false;
        $this->reset();
        parent::set($elements);
    }

    public function doSort()
    {
        if ($this->sortedFlag) {
            return $this->sorted;
        }
        $this->sorted = array();
        $this->parents = array();
        $this->grouped = array();

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        $prevId = null;
        $prevParents = $currentGroup = [];

        for ($i = 0; $i < count($this->sorted); $i++) {
            $curId = $this->sorted[$i];
            $prevParents = array_unique(array_merge($prevParents, $this->parents[$curId]));
            if ($prevId && in_array($prevId, $prevParents)) {
                $this->grouped[] = $currentGroup;
                $currentGroup = $prevParents = [];
            }
            $currentGroup[] = $curId;
            $prevId = $curId;
        }
        $this->grouped[] = $currentGroup;

        $this->sortedFlag = true;
        return $this->sorted;
    }

    public function grouped()
    {
        if (!$this->sortedFlag) {
            $this->sort();
        }
        return $this->grouped;
    }

    protected $sortedFlag = false;
    public function sorted()
    {
        if (!$this->sortedFlag) {
            $this->sort();
        }
        return $this->sorted;
    }

    protected function reset()
    {
        foreach ($this->elements as $element) {
            $element->visited = false;
        }
    }
}
