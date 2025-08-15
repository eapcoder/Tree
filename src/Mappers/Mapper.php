<?php

declare(strict_types=1);

namespace Tree\Mappers;

use Tree\Collection\Collection;
use Tree\Conf\Registry;
use Tree\DomainObject;
use Tree\Exception\AppException;
use Tree\ObjectWatcher;


abstract class Mapper
{
    protected \PDO $pdo;

    public function __construct()
    {
        $reg = Registry::instance();
        $this->pdo = $reg->getPdo();
       
    }

    public function find(int $id): DomainObject
    {
       
        $old = $this->getFromMap($id);
      
        if (! is_null($old)) {
            return $old;
        }

        $this->selectstmt()->execute([$id]);
        $raw = $this->selectstmt()->fetch();
        $this->selectstmt()->closeCursor();
       
        if (! is_array($raw) || ! isset($raw['id'])) {
           throw new AppException('Sql record not fonud');
        }
        
        $object = $this->createObject($raw);
       
     
        return $object;
    }


    /**
     * //TODO Search with criteria, need test
     * @return DomainObject
     */
    public function findBy(array $criteria): DomainObject | null
    {

        //TODO 0708
        if (! is_int($criteria)) {
            $uniqidId = uniqid();
            $old = $this->getFromMap($uniqidId, $findby = true);
        } else {
            $old = $this->getFromMap($criteria);
        }

        if (! is_null($old)) {
            return $old;
        }
        
        $newcriteria = array_values($criteria);
        $stmp = $this->selectBystmt($criteria);
        $stmp->bindParam(':name', $newcriteria[0], \PDO::PARAM_STR);
        $stmp->execute();

        $raw = $stmp->fetch();
        $stmp->closeCursor();
        //dump($raw);
        if (! is_array($raw)) {
            return null;
        }

        if (! isset($raw['id'])) {
            return null;
        }

        $object = $this->createObject($raw);

        return $object;
    }

    
    private function getFromMap($id, $findBy = false): ?DomainObject
    {
     
        return ObjectWatcher::exists(
            $this->targetClass(),
            $id
        );
    }

    private function addToMap(DomainObject $obj): DomainObject
    {
        return ObjectWatcher::add($obj);
    }

    // Mapper

    public function createObject($raw): DomainObject
    {
       
        $old = $this->getFromMap($raw['id']);

        if (! is_null($old)) {
            return $old;
        }
       
        $obj = $this->doCreateObject($raw);
        $this->addToMap($obj);
       
        return $obj;
    }

    public function insert(DomainObject $obj): void
    {
        $this->doInsert($obj);
        $this->addToMap($obj);
    }

    public function findAll(): Collection
    {
        $this->selectAllStmt()->execute([]);
        return $this->getCollection(
            $this->selectAllStmt()->fetchAll()
        );
    }


    abstract protected function selectAllStmt(): \PDOStatement;
    abstract protected function getCollection(array $raw): Collection;
    abstract protected function update(DomainObject $object): void;
    abstract protected function doCreateObject(array $raw): DomainObject;
    abstract protected function doInsert(DomainObject $object): void;
    abstract protected function selectStmt(): \PDOStatement;
    abstract protected function selectByStmt(array $criteria): \PDOStatement;
}
