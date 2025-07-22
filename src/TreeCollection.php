<?php

declare(strict_types=1);

namespace Tree;

class TreeCollection extends Collection
{
    public function targetClass(): string
    {
        return Tree::class;
    }
}
