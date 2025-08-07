<?php

declare(strict_types=1);

namespace Tree;

use Tree\Collection\ChildCollection;
use Tree\Conf\Registry;
use Tree\Mappers\TreeMapper;

class Tree extends DomainObject
{
    private ?ChildCollection $childs = null;
    private ?bool $hasChild = false;

    public function __construct(int $id, private string $name)
    {
        parent::__construct($id);
    }


    public function getChilds(): ChildCollection
    {
        if (is_null($this->childs)) {
            $reg = Registry::instance();
            $this->childs = $reg->getChildCollection();
        }

        return $this->childs;
    }

    public function setChilds(ChildCollection $childs): void
    {
        $this->childs = $childs;
        $this->hasChild = true;
    }

    public function addChild(Child $child): void
    {
        $this->getChilds()->add($child);
        $child->setTree($this);
        $this->hasChild = true;
       
    }

    // Tree

    public function getChilds2(): ChildCollection
    {
        if (is_null($this->childs)) {
            $reg = Registry::instance();
            $finder = $reg->getChildMapper();
            $this->childs = $finder->findByTree($this->getId());
        }

        return $this->childs;
    }

    public function getFinder(): TreeMapper
    {
        $reg = Registry::instance();

        return $reg->getTreeMapper();
    }

    public function setName($name): void
    {
        $this->name = $name;
        $this->markDirty();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasChilds(): bool
    {
        return $this->hasChild;
    }

    public function save()
    {
        ObjectWatcher::instance()->performOperations();
        
    }

}
