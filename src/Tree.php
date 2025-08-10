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
    protected ?int $lft = 0;
    protected ?int $rgt = 0;
    protected ?int $lvl = null;
    public function __construct(int $id, private string $name, private int|null $parent_id = null)
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

    public function getParent(): int|null
    {
        return $this->parent_id;
    }

    public function setParent($parent)
    {
        $treeMapper = $this->getFinder();
        $treeMapper->updateParent($parent, $this);
        
        
    }


    public function up(): void
    {
        dump($this->getParent());
        $treeMapper = $this->getFinder();
        /* $treeMapper->selectStmt()->execute([$this->getParent()]);
        $parent = $treeMapper->selectstmt()->fetch();
        $treeMapper->selectstmt()->closeCursor(); */
        $parent = $treeMapper->find($this->getParent());
        dump($parent->getParent());
        $this->setParent($parent);

    }

    public function down(): void
    {
        
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

    public function getLvl(): int
    {
        return $this->lvl;
    }

    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
        return $this;
    }

}
