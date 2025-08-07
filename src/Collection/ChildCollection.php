<?php

declare(strict_types=1);

namespace Tree\Collection;

use Tree\Child;

class ChildCollection extends Collection
{
    public function targetClass(): string
    {
        return Child::class;
    }
}
