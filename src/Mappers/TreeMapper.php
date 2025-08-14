<?php

declare(strict_types=1);

namespace Tree\Mappers;

use Tree\Child;
use Tree\Collection\TreeCollection;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\DomainObject;
use Tree\Exception\AppException;
use Tree\Helpers\Html;
use Tree\Helpers\TreeRebuilder;
use Tree\Tree;

class TreeMapper extends Mapper
{
    use TreeRebuilder;

    private \PDOStatement $selectStmt;
    private \PDOStatement $selectNullStmt;
    private \PDOStatement $selectByStmt;
    private \PDOStatement $selectTreeStmt;
    private \PDOStatement $selectAllStmt;
    private \PDOStatement $updateStmt;
    private \PDOStatement $removeStmt;
    private \PDOStatement $updateParentStmt;
    private \PDOStatement $insertStmt;
    private $parentId;
    public function __construct(private $table = 'categories', private $options = array())
    {
        parent::__construct();


        $this->selectStmt = $this->pdo->prepare(
            "SELECT * FROM $table WHERE id=?"
        );

        $this->selectNullStmt = $this->pdo->prepare(
            "SELECT * FROM $table WHERE parent_id IS ?"
        );

        $this->selectAllStmt = $this->pdo->prepare(
            "SELECT * FROM $table"
        );

        $this->updateStmt = $this->pdo->prepare(
            "UPDATE $table SET name=?, id=? WHERE id=?"
        );

        $this->removeStmt = $this->pdo->prepare(
            "DELETE FROM $table WHERE id=?"
        );

        $this->updateParentStmt = $this->pdo->prepare(
            "UPDATE $table SET parent_id=? WHERE id=?"
        );

        $this->insertStmt = $this->pdo->prepare(
            "INSERT INTO $table ( name, lft, rgt ) VALUES( ?, ?, ? )"
        );

        $this->selectTreeStmt = $this->pdo->prepare(
            "WITH RECURSIVE r (id, parent_id, name, lvl, lft, rgt)
            AS (
                SELECT id, parent_id, name, lvl, lft, rgt
                FROM $table 
                WHERE id = ?

                UNION ALL

                SELECT $table.id,
                       $table.parent_id,
                       $table.name,
                       $table.lvl,
                       $table.lft,
                       $table.rgt
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

        $obj = new Tree((int)$array['id'], $array['name'], $array['parent_id'], $array['lft'], $array['rgt'], $array['lvl']);

        if ($withChild) {
            $childMapper = new ChildMapper();
            $child = $childMapper->findByTree($array['id']);

            if ($child) {

                $obj->addChild($child);
            }
        }

        return $obj;
    }

    protected function doInsert(DomainObject $object): void
    {
        $values = [$object->getName(), 1, 2];
        try {
            $stmt = $this->pdo->prepare("SELECT rgt FROM categories WHERE id = :parent_id");
            $stmt->execute([':parent_id' => $object->getParent()]);
            $parent_rgt = $stmt->fetchColumn();


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

    public function remove(DomainObject $object): void
    {
        $values = [$object->getId()];
        $this->removeStmt->execute($values);

        if ($this->removeStmt->execute($values)) {
            $this->rebuild($object);
        }
    }

    public function moveLevelUp(DomainObject $object): array
    {

        return $this->moveUpandRebuid($object);
    }

    public function moveUp(DomainObject $object): void
    {

        $this->moveUpInOneLevel($object);
    }


    public function remove2(): int
    {
        return 123;
    }
    public function updateParent(DomainObject $object, $current): void
    {

        $values = [$object->getParent(), $current->getId()];
        dump($values);
        $this->updateParentStmt->execute($values);
    }

    public function selectStmt(): \PDOStatement
    {
        return $this->selectStmt;
    }

    public function selectNullStmt(): \PDOStatement
    {
        return $this->selectNullStmt;
    }


    public function selectByStmt($criteria): \PDOStatement
    {
        $sel = '';
        foreach ($criteria as $key => $item) {
            $sel .= $key . '=:' . $key . ',';
        }
        $sel = !empty($sel) ? substr($sel, 0, -1) : '';

        $this->selectByStmt = $this->pdo->prepare(
            "SELECT * FROM $this->table WHERE $sel"
        );
        // dump($this->selectByStmt);
        return $this->selectByStmt;
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

    function makeParentChildRelations(&$inArray, &$outArray, $currentParentId = null)
    {
        if (!is_array($inArray)) {
            return;
        }
        if (!is_array($outArray)) {
            return;
        }


        foreach ($inArray as $tuple) {

            if ($tuple['parent_id'] == $currentParentId) {

                $tuple['children'] = array();
                $this->makeParentChildRelations($inArray, $tuple['children'], $tuple['id']);
                $outArray[] = $tuple;
            }
        }
    }


    public function getTree($id = null)
    {
        if (empty($id)) {
            $raws = [];
            //$raws[] = ['parent_id' => null];
            $this->selectNullStmt->execute([null]);
            $parentRaws =  $this->selectNullStmt->fetchAll();

            foreach ($parentRaws as $elem) {

                $this->selectTreeStmt->execute([$elem['id']]);
                $raws[] = $this->selectTreeStmt->fetchAll();
            }
            $newArr = [];
            foreach ($raws as $key => $raw) {
                if (is_array($raw)) {
                    foreach ($raw as $k => $v) {

                        array_push($newArr, $v);
                    }
                }
            }


            //dump($newArr);
            //array_unshift($raws[1], $raws[0][0]);
            $raws = $newArr;
            // dump($raws); 


        } else {
            $this->parentId = $id;
            $this->selectTreeStmt->execute([$id]);
            $raws =  $this->selectTreeStmt->fetchAll();

            foreach ($raws as $key => $raw) {
                $raws[$key] = array_filter($raw, function ($key) {
                    return $key != '0' && $key != '1' && $key != '2';
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $parentChild = array();
        $raws[0]['parent_id'] = null;
        //ksort($raws);


       
        $this->makeParentChildRelations($raws, $parentChild);
        
        if (isset($this->options['html']) && $this->options['html'] === true) {
            $reg = Registry::instance();
                       
            return Html::generate($parentChild, $reg->getConf()->get('dev'));
        } else if (in_array('simpleArray', $this->options) && $this->options['simpleArray'] === true) {
            return $parentChild;
        } else {
            foreach ($parentChild as $raw) {

                if ($raw['parent_id'] == NULL || $raw['parent_id'] == $raws[0]['id']) {
                    //print 'parent_id++++' . PHP_EOL;

                    $obj = $this->doCreateObject($raw, false);
                    //print '<br>++++' . PHP_EOL;
                }

                if (count($raw['children']) > 0) {
                    // print 'count($raw->children)' . PHP_EOL;
                    //dump($obj);
                    //dump($raw);
                    //if(!empty($raw->children)) {

                    //echo '<br>hasChilds'  . PHP_EOL;
                    $childs = null;
                    $childs = $this->generateChilds($raw, $obj, $raw['id']);
                    //print 'childs' . PHP_EOL;
                    //dump($childs);
                    
                    //}

                }
            }
            return $obj;
        }
    }

    public function generateChilds($raw, $obj, $id, &$parentChild = null)
    {
       
        //$getchild->add($child);
        //print '<br>dump($obj) <<<<' . PHP_EOL;
        //dump($this->parentId);
        $child = [];
        foreach ($raw['children'] as $rawchild) {

            /*   if (!$parentChild && $id == $this->parentId) {
                dump($rawchild['name']);
                $parentChild = new Child($rawchild['id'], $rawchild['name'], $rawchild['parent_id'], $rawchild['lvl']);
                $parentChild->hasParent = true;
                dump($parentChild);
            }  */

        
            $child = new Child($rawchild['id'], $rawchild['name'], $rawchild['parent_id'], $rawchild['lvl']);
           
            
                if($parentChild) {
                    $parentChild->addChild($child);
                    $parentChild->hasParent = true;
                } else {
                    $obj->addChild($child);
                }

                if (count($rawchild['children'])) {
                   
                    $this->generateChilds($rawchild, $obj, $rawchild['id'], $child);
                }
            
        }

        return $obj;


        /* 
        print '<br>$generateChilds >>>>' . PHP_EOL;
        dump($obj);
        print '<br>$generateChilds <<<<' . PHP_EOL; 
        */
    }

    //TODO another way
    function buildTree(array $elements, $parentId = null)
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] === $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
