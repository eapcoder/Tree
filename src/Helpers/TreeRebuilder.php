<?php

declare(strict_types=1);

namespace Tree\Helpers;

use Tree\Child;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\Mapper;
use Tree\Tree;


trait TreeRebuilder
{
    use ExperimentalFunctions;

    public ?bool $withRoot = false;
    public static ?string $html = '';
    protected \PDO $pdo;

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

    /**
     * Смена текущего с верхним элементом child на одном уровне
     */
    public function moveUpInOneLevel($object) {
        $id = $object->getId();
       /*  dump($object);
        dump($this->getTotal($object->getParent(), $object->getLvl())); */
        $parent = $this->getTree($object->getParent());
                
        $items = [];
        $i = 0;
        foreach($parent->getChilds() as $child) {
            ++$i;
            $items[$i] = $child;
            if ($id ==  $child->getId()) { // смена текущего с верхним элементом child
                if ($i > 1) {
                    $prev = $items[$i-1];
                    $items[$i-1] = $child;
                    $items[$i] = $prev;
                    dump($i);
                    dump($items);
                    //$items = array_reverse($items);

                }
            } else { // присоединнеие остальных child
                $items[$i] = $child;
                
            }
           
          $child->remove(); // физическое удаление child
          
        }
        dump($items);
        $parent = $this->find($object->getParent());
        $parent->removeChilds();
        
        foreach($items as $item) { // воссоздание child с учётом смена текущего с верхним элементом child
            $item->setId(-1);
            $item->markNew();
            $parent->addChild($item);
        }
        dump($parent);
        $parent->save(); 
         

    }

    /**
     * //TODO
     */
    public function moveDownInOneLevel($object) {}



}
