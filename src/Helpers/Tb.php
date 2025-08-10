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


    public function generate($tree = null, &$left = 0, &$i = 0, $right = 0, &$t = null, &$common = null, &$level = null, &$lastlvl = null)
    {
        //trace();
        $level = $level ?? 0;
        $common = $common ?? [];
        if ($i == 0) {
            $t = $this->getTotal($tree->getId());
            $left = 1;
            $right = $t * 2;
            $tree->setLeft($left);
            $tree->setRight($right);
            ++$level;
            //dump('$level = ' . $level);
            dump($tree);
        }
        //if ($tree instanceof Tree) { }
        //$common[$level]['total'] = $t;


        $k = 0;
        foreach ($tree->getChilds() as $key => $childs) {


            ++$i;
            ++$k;

            $common[$level]['total'] = $t;
            $common[$level]['cycle'] = $k;
            $common[$level]['cycleWithChild'] = $i;
            $c = count($childs->getChilds());
            $left = $left + $k;


            dump('$lastlvl =' . $lastlvl);
            dump($lastlvl <=> $childs->getLvl());
            dump('$level = ' . $childs->getLvl());


            if ($childs->hasChilds()) {

                // $left = $left - 1;   
                $t = $this->getTotal($childs->getId());
                $right = $t * 2 + $i;
            } else {
                //dump('$t = ' . $t);
                //dump('$i = ' . $i);
                //dump('$k = ' . $k);
                //$left = $common[$level]['total'] + $common[$level]['cycle'];
                $left = $common[$level]['cycleWithChild'] * (($common[$level]['cycle'] == 1) ? 2 : $common[$level]['cycle']) - 2;
                $right = $common[$level]['cycleWithChild'] * (($common[$level]['cycle'] == 1) ? 2 : $common[$level]['cycle']) - 1;

                if ($lastlvl <=> $childs->getLvl()) { // 1
                    ++$left;
                    ++$right;
                } else if (($lastlvl <=> $childs->getLvl()) == -1) {
                    $left = $left - 2;
                    $right = $right - 2;
                } else if (($lastlvl <=> $childs->getLvl()) == 0) {
                    $left = $left + 1;
                    $right = $right + 1;
                }

                //dump($childs);
            }
            $lastlvl = $childs->getLvl();
            $childs->setLeft($left);
            $childs->setRight($right);

            dump($childs);
            /*  
            dump($childs->hasChilds());
            dump($childs);
            dump($right); 
            */
            if ($childs->hasChilds()) {

                $k = 0;
                ++$level;
                $this->generate($childs, $left, $i, $right, $t, $common, $level, $lastlvl);
            }
        }
    }

    function getTotal($id)
    {
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
