<?php

declare(strict_types=1);

namespace Tree;

class EventCollection extends Collection
{
    public function targetClass(): string
    {
        return Event::class;
    }
}
