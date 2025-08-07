<?php

declare(strict_types=1);

namespace Tree\Collection;

use Tree\Event;

class EventCollection extends Collection
{
    public function targetClass(): string
    {
        return Event::class;
    }
}
