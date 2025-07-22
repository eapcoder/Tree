<?php

declare(strict_types=1);

namespace Tree;

class ChildCollection extends Collection
{
    public function targetClass(): string
    {
        return Child::class;
    }
}
