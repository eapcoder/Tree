<?php

declare(strict_types=1);

namespace Tree\Helpers;

use Tree\Child;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\Mapper;
use Tree\Tree;

/**
 * Experimental function 
 * 
 */

trait TreeRebuilder
{
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


    public function generate($tree = null, &$left = 0, &$right = 0, &$t = null, &$rightParent = null, &$lastlvl = null, $UpLevel = null)
    {
        //trace();
        $level = $level ?? 0;

        $common = $common ?? [];
        if($tree instanceof Tree){
            
            $left = $right = 1;
            $tree->setLeft($left);
            $tree->setRight($this->getTotal($tree->getId()) * 2);
            ++$level;
            //dump($tree);
            //dump($this->getTotal($tree->getId()));
        }

        $down = 0;
        foreach($tree->getChilds() as $key => $childs) {
            if(!$UpLevel) {
                $left = $right = $right + 1;
                
            } else {
                $left = $left + 1;
                $UpLevel = false;
            }
            $up = (($lastlvl <=> $childs->getLvl()) == 1);
           
            //dump($up);
            //dump($down);
            if ($childs->hasChilds()) {
                
                $rightParent = $this->getTotal($childs->getId()) * 2 + $left - (($up) ? 0 : 1);
                $childs->setRight($rightParent);
              
                if ($up) { // 1
                    $left  = $left + 1;
                    $right = $rightParent;
                    $UpLevel = true;
                }
                             
            } else {
                if ($up) { // 1
                    $left  = $rightParent + 1;
                   
                }
                $right = $left + 1;
                $childs->setRight($right);
            }

            $lastlvl = $childs->getLvl();
            $childs->setLeft($left);

            //dump('$left = ' . $left . ' $right = ' . $right);
            //dump($childs);
           
            if($childs->hasChilds()) {

                   $this->generate($childs, $left, $right, $t, $rightParent, $lastlvl, $UpLevel);
            }

           
        }
 
    }

    function getTotal ($id) {
        $this->init();
        $t = $this->pdo->prepare(
            "WITH RECURSIVE r (id, parent_id, name)
            AS (
                SELECT id, parent_id, name
                FROM categories 
                WHERE id = $id

                UNION ALL

                SELECT categories.id,
                       categories.parent_id,
                       categories.name
                FROM   categories, r
                WHERE (r.id = categories.parent_id)
            )
            SELECT COUNT(*) FROM r ORDER by id ASC;"
        );

        $t->execute();
        $f = $t->fetch();
        return $f[0];
    }

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
}
