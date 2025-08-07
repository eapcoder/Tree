<?php

declare(strict_types=1);

namespace Tree;

use Tree\Collection\EventCollection;
use Tree\Mappers\Mapper;

/* listing 13.31 */
class DeferredEventCollection extends EventCollection
{
    private bool $run = false;

    public function __construct(
        Mapper $mapper,
        private \PDOStatement $stmt,
        private array $valueArray
    ) {
        parent::__construct([], $mapper);
    }

    protected function notifyAccess(): void
    {
        if (! $this->run) {
            $this->stmt->execute($this->valueArray);
            $this->raw = $this->stmt->fetchAll();
            $this->total = count($this->raw);
        }

        $this->run = true;
    }
}
