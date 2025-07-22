<?php

declare(strict_types=1);

namespace Tree;

class TreeMapper extends Mapper
{
    private \PDOStatement $selectStmt;
    private \PDOStatement $selectTreeStmt;
    private \PDOStatement $selectAllStmt;
    private \PDOStatement $updateStmt;
    private \PDOStatement $insertStmt;
    
    public function __construct(private $table = 'categories', private $options = array())
    {
        parent::__construct();
        $this->selectStmt = $this->pdo->prepare(
            "SELECT * FROM $table WHERE id=?"
        );

        $this->selectAllStmt = $this->pdo->prepare(
            "SELECT * FROM $table"
        );

        $this->updateStmt = $this->pdo->prepare(
            "UPDATE $table SET name=?, id=? WHERE id=?"
        );
        $this->insertStmt = $this->pdo->prepare(
            "INSERT INTO $table ( name ) VALUES( ? )"
        );
 
        $this->selectTreeStmt = $this->pdo->prepare(
            "WITH RECURSIVE r (id, parent_id, name)
            AS (
                SELECT id, parent_id, name
                FROM $table 
                WHERE id = ?

                UNION ALL

                SELECT $table.id,
                       $table.parent_id,
                       $table.name
                FROM   $table, r
                WHERE (r.id = $table.parent_id)
            )
            SELECT * FROM r ORDER by id ASC;"
        );
    }

    protected function targetClass(): string
    {
        return Tree::class;
    }

    public function getCollection(array $raw): TreeCollection
    {
        return new TreeCollection($raw, $this);
    }

    protected function doCreateObject(array $array, $withChild = true): Tree
    {
        $obj = new Tree((int)$array['id'], $array['name']);
        if($withChild) {
            $childMapper = new ChildMapper();
            $child = $childMapper->findByTree($array['id']);
            $obj->addChild($child);
        }

        return $obj;
    }

    protected function doInsert(DomainObject $object): void
    {
        $values = [$object->getName()];
        try {
            $this->insertStmt->execute($values);
            $id = $this->pdo->lastInsertId();
        } catch (AppException $e) {
            print $e->getMessage();
        }

        //dump($object);
        $object->setId((int)$id);
    }

    public function update(DomainObject $object): void
    {
        $values = [$object->getName(), $object->getId(), $object->getId()];
        $this->updateStmt->execute($values);
    }

    public function selectStmt(): \PDOStatement
    {
        return $this->selectStmt;
    }

    public function selectAllStmt(): \PDOStatement
    {
        return $this->selectAllStmt;
    }

    // test nested array
    public function getTree2($id)
    {

        $this->selectTreeStmt->execute([$id]);
        $raws =  $this->selectTreeStmt->fetchAll();
        $childMapper = new ChildMapper();
        foreach ($raws as $raw) {
            //dump($raw);
        }
        /* Tree structure
            - 1
              --6
                --8
                  --14
              --7
                --13
              --9
            - 2

        */
        $inArray = array(
            array('id' => '1', 'parent_id' => '0'),
            array('id' => '2', 'parent_id' => '0'),
            array('id' => '6', 'parent_id' => '1'),
            array('id' => '7', 'parent_id' => '1'),
            array('id' => '8', 'parent_id' => '6'),
            array('id' => '9', 'parent_id' => '1'),
            array('id' => '13', 'parent_id' => '7'),
            array('id' => '14', 'parent_id' => '8'),
        );
        //dump($inArray);
        $outArray = array();
        $this->makeParentChildRelations($inArray, $outArray);
        //dump($outArray);

    }

    function makeParentChildRelations(&$inArray, &$outArray, $currentParentId = 0)
    {
        if (!is_array($inArray)) {
            return;
        }
        if (!is_array($outArray)) {
            return;
        }
        if($currentParentId == NULL) $currentParentId = 0;
        foreach ($inArray as $key => $tuple) {
            if ($tuple['parent_id'] == $currentParentId) {
                $tuple['children'] = array();
                $this->makeParentChildRelations($inArray, $tuple['children'], $tuple['id']);
                $outArray[] = $tuple;
            }
        }
    }

    public function getTree($id)
    {
        $this->selectTreeStmt->execute([$id]);
        $raws =  $this->selectTreeStmt->fetchAll();
        $childMapper = new ChildMapper();

        foreach($raws as $key => $raw) {
            $raws[$key] = array_filter($raw, function ($key) {
               
                return $key != '0' && $key != '1' && $key != '2';
            }, ARRAY_FILTER_USE_KEY);
        }
           
        $parentChild = array();
        $this->makeParentChildRelations($raws, $parentChild);
        //dump($parentChild);
      
        if(in_array('html', $this->options) && $this->options['html'] === true) {
         
            return Html::generate($parentChild);
        } else if(in_array('simpleArray', $this->options) && $this->options['simpleArray'] === true) {
            return $parentChild;
        } else {
            foreach($parentChild as $raw) {
                //dump($raw);
                if($raw['parent_id'] == NULL) {
                   //print 'parent_id++++' . PHP_EOL;
                    
                   $obj = $this->doCreateObject($raw, false);
                   //print '<br>++++' . PHP_EOL;
                } 
                
                if(count($raw['children']) > 0) {
                   // print 'count($raw->children)' . PHP_EOL;
                    //dump($obj);
                    //dump($raw);
                    //if(!empty($raw->children)) {
                      
                        //echo '<br>hasChilds'  . PHP_EOL;
                        $childs = null;
                        $childs = $this->generateChilds($raw, $obj, $raw['id']);
                        //print 'childs' . PHP_EOL;
                        //dump($childs);
                        $obj->addChild($childs);
                    //}
                    
                }
                
            }
            return $obj;
        }
    }
    
    public function generateChilds($raw, $obj, $id, $parentChild = null) {
        //$getchild->add($child);
        //print '<br>dump($obj) <<<<' . PHP_EOL;
        //dump($parentChild);
        foreach ($raw['children'] as $rawchild) {

             if (!$parentChild && $id == 1) {
                $parentChild = new Child($rawchild['id'], $rawchild['name'], $rawchild['parent_id']);
                $parentChild->hasParent = true;
            } else {
                $child = new Child($rawchild['id'], $rawchild['name'], $rawchild['parent_id']);
                $child->hasParent = true;
                $parentChild->addChild($child);
            } 
                   
            if (count($rawchild['children'])) {
                $this->generateChilds($rawchild, $obj, $rawchild['id'], $id == 1 ? $parentChild : $child);
            }

        }

        return $parentChild;

       
        /* 
        print '<br>$generateChilds >>>>' . PHP_EOL;
        dump($obj);
        print '<br>$generateChilds <<<<' . PHP_EOL; 
        */
    }

}
