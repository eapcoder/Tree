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
    
    
    public function __construct(private string $name, private int|null $parent_id = null, private int $lft = 0, private int  $rgt = 0, private $lvl = null)
    {
        parent::__construct();
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

    public function removeChilds(): void
    {
        $this->childs = null;
        $this->hasChild = false;
    }

    public function addChild(Child $child): void
    {
        
        $this->getChilds()->add($child);
        $child->setTree($this);
        $this->hasChild = true;
       
    }

    public function addChildTest(Child $child): void
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


    public function checkIsNewTreeElement()
    {
        foreach($this->getChilds() as $child) {
            dump($child);
        }
        
    }

    public function save()
    {
        if($this) {

        }
        
        return ObjectWatcher::instance()->performOperations();
        
        
    }


    public function update()
    {

       
        foreach($this->getChilds() as $key => $child) {
            $child->setId(-1);
            $child->setParent($this->getId());
            $child->setExist(false);
            ObjectWatcher::instance()->addNew($child);
            
        }

        $news = ObjectWatcher::instance()->getNew();
        dump($news[0] == $news[1]);
       dump(ObjectWatcher::instance()->getNew());
        return ObjectWatcher::instance()->performOperations($this->getId());
    }

    public function moveLevelUp(): mixed
    {
        return $this->getFinder()->moveLevelUp($this);
    }


    public function moveUp(): void
    {
        $this->getFinder()->moveUp($this);
    }

    public function moveDown(): void
    {
        $this->getFinder()->moveDown($this);
    }

    public function remove(): void
    {
        $this->getFinder()->remove($this);
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
