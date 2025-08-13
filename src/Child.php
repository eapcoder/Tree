<?php

declare(strict_types=1);

namespace Tree;

use Tree\Collection\ChildCollection;
use Tree\Collection\EventCollection;
use Tree\Conf\Registry;
use Tree\Mappers\ChildMapper;

class Child extends DomainObject
{
    private ?EventCollection $events = null;
    private ?ChildCollection $childs = null;
    private ?bool $hasChild = false;
    public ?bool $hasParent = false;
    protected ?int $lft = 0;
    protected ?int $rgt = 0;

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


    public function setEvents(EventCollection $collection): void
    {
        $this->events = $collection;
    }

    public function getEvents(): EventCollection
    {
       
        return $this->events;
    }


    public function getEvents2(): EventCollection
    {
      
        if (is_null($this->events)) {
            $reg = Registry::instance();
            $eventmapper = $reg->getEventMapper();
            $this->events = $eventmapper->findBySpaceId($this->getId());
        }

        return $this->events;
    }


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

    public function getLeft(): int
    {
        return $this->lft;
    }

    public function setLeft($lft)
    {
        $this->lft = $lft;
        return $this;
    }

    public function getRight(): int
    {
        return $this->rgt;
    }

    public function setRight($rgt)
    {
        $this->rgt = $rgt;
        return $this;
    }

    public function remove(): void
    {
        $this->getFinder()->remove($this);
    }

}
