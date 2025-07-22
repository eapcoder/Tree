<?php

declare(strict_types=1);

namespace Tree;

class Child extends DomainObject
{
    private ?EventCollection $events = null;
    private ?ChildCollection $childs = null;
    private ?bool $hasChild = false;
    public ?bool $hasParent = false;

    public function __construct(int $id, private string $name, private int $parent_id, private ?int $lvl = null, private ?Tree $tree = null)
    {
        $this->name = $name;
        $this->parent_id = $parent_id;
        
        parent::__construct($id);
        $this->tree = $tree;
    }

    /* listing 13.27 */

    // Space

    public function addChild(Child $child): void
    {
        $this->getChilds()->add($child);
        if(!empty($this->getTree())) {
            $child->setTree($this->getTree());
            
        }
        $this->hasChild = true;
        $this->hasParent = true;
        $child->hasParent = true;
    }

    public function getChilds(): ChildCollection
    {
        if (is_null($this->childs)) {
            $reg = Registry::instance();
            $this->childs = $reg->getChildCollection();
        }

        return $this->childs;
    }

    public function setTree(Tree $tree): void
    {
        $this->tree = $tree;
        $this->markDirty();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->markDirty();
    }
    /* /listing 13.27 */

    public function setEvents(EventCollection $collection): void
    {
        $this->events = $collection;
    }

    public function getEvents(): EventCollection
    {
       
        return $this->events;
    }

    /* listing 13.30 */

    // Space

    public function getEvents2(): EventCollection
    {
      
        if (is_null($this->events)) {
            $reg = Registry::instance();
            $eventmapper = $reg->getEventMapper();
            $this->events = $eventmapper->findBySpaceId($this->getId());
        }

        return $this->events;
    }
/* /listing 13.30 */

    public function forgetEvents(): void
    {
        $this->events = null;
    }

    public function getTree(): Tree|bool|null
    {
        return $this->tree;
    }

    public function getFinder(): ChildMapper
    {
        $reg = Registry::instance();
        return $reg->getChildMapper();
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function getParent(): int
    {
        return $this->parent_id;
    }
    public function setParent($pid)
    {
        $this->parent_id = $pid;
        $this->hasParent = true;
        return $this;
    }

    
    public function getLvl(): int
    {
        return $this->lvl;
    }

    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
        return $this;
    }

    public function hasChilds(): bool
    {
        return $this->hasChild;
    }
}
