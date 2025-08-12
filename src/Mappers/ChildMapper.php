<?php

declare(strict_types=1);

namespace Tree\Mappers;

use Tree\Child;
use Tree\Collection\ChildCollection;
use Tree\DomainObject;
use Tree\Exception\AppException;

class ChildMapper extends Mapper
{
    private \PDOStatement $selectStmt;
    private \PDOStatement $selectAllStmt;
    private \PDOStatement $selectTreeStmt;
    private \PDOStatement $updateStmt;
    private \PDOStatement $insertStmt;
    private \PDOStatement $findByTreeStmt;
    private \PDOStatement $updateLvlStmt;
    private \PDOStatement $selectByStmt;

    public function __construct()
    {
        parent::__construct();
        $this->selectByStmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE id=?"
        );

        $this->selectStmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE id=?"
        );
        $this->updateStmt = $this->pdo->prepare(
            "UPDATE categories SET name=?, id=? WHERE id=?"
        );
        $this->insertStmt = $this->pdo->prepare(
            "INSERT into categories ( name, parent_id, lvl, lft, rgt ) VALUES( ?, ?, ?, ?, ?)"
        );

        $this->selectAllStmt = $this->pdo->prepare(
            "SELECT * FROM categories"
        );

        $this->findByTreeStmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE parent_id=?"
        );

        $this->updateLvlStmt = $this->pdo->prepare(
            "UPDATE categories SET lvl=? WHERE id=?"
        );

     
    }

    protected function getCollection(array $raw): ChildCollection
    {
        return new ChildCollection($raw, $this);
    }

/* listing 13.29 */

    // ChildMapper

    protected function doCreateObject(array $raw): Child
    {
        
        $obj = new Child((int)$raw['id'], $raw['name'], $raw['parent_id'],  $raw['lvl']);
       
        $treeMapper = new TreeMapper();
        //$tree = $treeMapper->find((int)$raw['parent_id']);
        //$obj->setTree($tree);

        //$EventMapper = new EventMapper();
        //$eventCollection = $EventMapper->findByChildId((int)$raw['id']);
        //$obj->setEvents($eventCollection);

        return $obj;
    }
/* /listing 13.29 */

    // SpaceMapper

    protected function targetClass(): string
    {
        return Child::class;
    }

    protected function doInsert(DomainObject $object): void
    {
        $tree = $object->getTree();

       
        if (! $tree && !$object->hasParent) {
            throw new AppException("cannot save without prent tree");
        }

        $stmt = $this->pdo->prepare("SELECT rgt FROM categories WHERE id = :parent_id");
        $stmt->execute([':parent_id' => $object->getParent()]);
        $parent_rgt = $stmt->fetchColumn();

        // Shift lft and rgt values to make space
        $this->pdo->exec("UPDATE categories SET rgt = rgt + 2 WHERE rgt >= $parent_rgt");
        $this->pdo->exec("UPDATE categories SET lft = lft + 2 WHERE lft > $parent_rgt");

        // Insert the new node
        $new_lft = $parent_rgt;
        $new_rgt = $parent_rgt + 1;
   
        
        $values = [ $object->getName(), $object->getParent(), $object->getLvl(), $new_lft, $new_rgt ];
        $this->insertStmt->execute($values);
        $id = $this->pdo->lastInsertId();
        $object->setId((int)$id);
    }

    public function update(DomainObject $object): void
    {
        $values = [$object->getname(), $object->getid(), $object->getId()];
        $this->updateStmt->execute($values);
    }

    protected function selectStmt(): \PDOStatement
    {
        return $this->selectStmt;
    }

    protected function selectAllStmt(): \PDOStatement
    {
        return $this->selectAllStmt;
    }

    protected function selectByStmt($criteria): \PDOStatement
    {
        return $this->selectByStmt;
    }

    public function findByTree($vid): Child | bool
    {
        $this->findByTreeStmt->execute([$vid]);
        $item = $this->findByTreeStmt->fetch();
        if($item) {
            $obj = $this->doCreateObject($item);
            return $obj;
        } else {
            return false;
        }
        
        
        //return new ChildCollection([$obj], $this);
    }
}
