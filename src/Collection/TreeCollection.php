<?php

declare(strict_types=1);

namespace Tree\Collection;

use Tree\Tree;

class TreeCollection extends Collection
{
    public function targetClass(): string
    {
        return Tree::class;
    }
}
