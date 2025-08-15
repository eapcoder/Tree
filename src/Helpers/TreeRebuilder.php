<?php

declare(strict_types=1);

namespace Tree\Helpers;

use Tree\Child;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\Mapper;
use Tree\Mappers\TreeMapper;
use Tree\Tree;

trait TreeRebuilder
{
    use ExperimentalFunctions;

    public ?bool $withRoot = false;
    public static ?string $html = '';
    protected \PDO $pdo;
    protected int $last = 0;

    public function init()
    {
        $config = dirname(__DIR__) . "/data/options.ini";
        $options = parse_ini_file($config, true);
        $conf = new Conf($options['mysql']);
        Registry::reset();
        $reg = Registry::instance();
       
        $reg->setConf($conf);
        $dsn = $reg->getDSN();
       
        if (is_null($dsn)) {
            throw new AppException("No DSN");
        }

        if ($conf->get('driver') == 'mysql') {
            $username = $conf->get('username');
            $password = $conf->get('password');
        }

        $this->pdo = new \PDO($dsn, $username ?? null, $password ?? null, [\PDO::ATTR_PERSISTENT => true]);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        //$this->pdo = $reg->getMyPdo();
    }



    /**
     * Amount of all child with all level at one element
     */
    function getTotal ($id, $level = null) {
        $this->init();
        $t = $this->pdo->prepare(
            "WITH RECURSIVE r (id, parent_id, name, lvl)
            AS (
                SELECT id, parent_id, name, lvl
                FROM categories 
                WHERE id = $id " . (!empty($level) ? ' AND lvl = ' . $level : '') . "

                UNION ALL

                SELECT categories.id,
                       categories.parent_id,
                       categories.name,
                       categories.lvl
                FROM   categories, r
                WHERE (r.id = categories.parent_id)
            )
            SELECT COUNT(*) FROM r ORDER by id ASC;"
        );

        $t->execute();
        $f = $t->fetch();
        return $f[0];
    }

    /**
     * Rebuild after remove
     */
    public function rebuild($object) {
        $tree = $this->getTree(1);
         
        if (! $tree && !$object->hasParent) {
            throw new AppException("cannot save without prent tree");
        }
        
        $target_rgt = $object->getRight();
        $target_lft = $object->getLeft();

        $this->pdo->exec("SET @width = $target_rgt - $target_lft + 1");
        // Shift lft and rgt values to make space
        $this->pdo->exec("DELETE FROM categories WHERE lft BETWEEN $target_lft AND $target_rgt");
        $this->pdo->exec("UPDATE categories SET rgt = rgt - @width WHERE rgt > $target_rgt");
        $this->pdo->exec("UPDATE categories SET lft = lft - @width WHERE lft > $target_rgt");

    }


    /**
     * Move element up in tree and insert it in first parent
     */
    public function moveUpandRebuid($object) {
        //dump('MOVEUP');
        //dump($object);
        $idParent = $object->getParent();
        //dump($this->getTotal($idParent));

        if(!empty($this->find($idParent))) {

            $parent = $this->find($idParent);
            $parentToInsert = $this->find($parent->getParent());
            //dump('MOVEUP');
            //dump($parentToInsert);
            //dump($object);
            $new = new Child(-1, $object->getName(), $parentToInsert->getId());
            $parentToInsert->addChild($new);
            $return = $parentToInsert->save();
            $object->remove();
          
            return $return;
        }
        
    }


    public function newGetTree($id = null)
    {
       
        //TODO check it
        if (empty($id)) {
            $raws = [];
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

            $raws = $newArr;
     

        } else {
            $treeMapper = new TreeMapper('categories');
           
            $treeMapper->selectTreeStmt->execute([$id]);
            $raws =  $treeMapper->selectTreeStmt->fetchAll();

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
            
            foreach ($parentChild as $raw) {

                if (count($raw['children']) > 0) {
                    $this->addWeight($raw);
                    $childs = null;
                    $childs = $this->newGenerateChilds($raw, $raw['id']);
       

                }
            }
            
        
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

    /**
     * Add weight to lft - rgt
     */
    public function addWeight($raw)
    {
        $stmt = $this->pdo->prepare("SELECT rgt FROM categories WHERE id = :parent_id");
        $stmt->execute([':parent_id' => $raw['parent_id']]);
        $parent_rgt = !!$raw['parent_id'] ? $stmt->fetchColumn() : 1;
       
        ++$this->last;
        if (!$raw['parent_id']) {
          
            $this->pdo->exec("UPDATE categories SET rgt = 2, lft = 1");
        }
        else {
            // Shift lft and rgt values to make space
            $this->pdo->exec("UPDATE categories SET rgt = rgt + 2 WHERE rgt >= $parent_rgt");
            $this->pdo->exec("UPDATE categories SET lft = lft + 1 WHERE lft >= $parent_rgt");
        }
        // Insert the new node
        
        $new_lft = $parent_rgt;
        $new_rgt = $parent_rgt + 1;
        $this->pdo->exec("UPDATE categories SET rgt = $new_rgt,  lft = $new_lft WHERE id = " . $raw['id'] . "");
        
        $this->last = $this->last +  $new_lft; 
    }

    public function newGenerateChilds($raw,$id, &$parentChild = null)
    {
        $child = [];
        foreach ($raw['children'] as $rawchild) {
           
            $this->addWeight($rawchild);
                if (count($rawchild['children'])) {

                $this->newGenerateChilds($rawchild, $rawchild['id'], $child);
            }
        }
    }

    /**
     * Rebuild after remove
     */
    public function testRebuild($object)
    {
        
        $tree = $this->newGetTree(1);
    }

    /**
     * Смена текущего с верхним элементом child на одном уровне
     */
    public function moveUpInOneLevel($object) {
        $id = $object->getId();

        $parent = $this->getTree($object->getParent());
      
        $items = [];
        $i = 0;
        $childToRemove = [];
        foreach($parent->getChilds() as $child) {
          
            ++$i;
            $items[$i] = $child;
            if ($id ==  $child->getId()) { // смена текущего с верхним элементом child
                if ($i > 1) {
                    $prev = $items[$i-1];
                    $items[$i-1] = $child;
                    $items[$i] = $prev;
                }
            } else { // присоединнеие остальных child
                $items[$i] = $child;
                
            }
            //$child->remove(); // физическое удаление child
 
        }

        $ids = $this->multiRemove($object,  $items);
        $ids = substr($ids, 1);
        $this->pdo->exec("DELETE FROM categories WHERE id IN ($ids)");
        $this->testRebuild($object);
      
        $parent = $this->find($object->getParent());
        $parent->removeChilds();
        
        foreach($items as $item) { // воссоздание child с учётом смена текущего с верхним элементом child
            $item->setId(-1);
            $item->markNew();
            $parent->addChild($item);
        }
        $parent->save();  
 
    }


    public function multiRemove($object,  $items, &$ids = '')
    {

        $ids = $ids ?? '';
        foreach ($items as $item) {
            $ids .= ',' . $item->getId();
            if ($item->getChilds()) {
                $this->multiRemove($object, $item->getChilds(), $ids);
            }
        }

        return $ids;

    }
    /**
     * //TODO
     */
    public function moveDownInOneLevel($object) {}



}
