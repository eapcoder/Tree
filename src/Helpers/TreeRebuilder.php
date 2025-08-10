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


    public function generate($tree = null, &$left = 0, &$right = 0, &$t = null, &$k = null)
    {
        //trace();
        $level = $level ?? 0;
        $k = $k ?? 0;
        $common = $common ?? [];
        if($tree instanceof Tree){
            $t = $this->getTotal($tree->getId());
            $left = 1;
            $right = $t * 2;
            $tree->setLeft($left);
            $tree->setRight($right);
            ++$level;
            //dump('$level = ' . $level);
            dump($tree);
        }

        $k = 0;
        foreach($tree->getChilds() as $key => $childs) {
            ++$k;
           
            if ($childs->hasChilds()) {
                $left = $left + 1;
                $t = $this->getTotal($childs->getId());
               
                    $right = $t * 2 + $childs->getLvl() - 1;
              
                
              
               
              
              
            } else {
                if($k == 1) {
                    $left  = $childs->getLvl()  * $k;
                    $right = $childs->getLvl()  * $k + 1;
                } else {
                    //$left = (int) ceil($right / 2) + $k + 1;
                    //$right = $childs->getLvl()  * $k;
                    $left  = $childs->getLvl()  * $k;
                    $right = $childs->getLvl()  * $k + 1;
                }
                
                dump($k);
            }
           
            $childs->setLeft($left);
            $childs->setRight($right);
            dump($childs);
            
           
            if($childs->hasChilds()) {
                $k = 0;
                $this->generate($childs, $left, $right, $t, $k);
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
}
