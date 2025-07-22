<?php

declare(strict_types=1);

namespace Tree;
use Tree\Conf;

class Runner
{
    public static function run()
    {
        self::setUp();
        $treeMapper = new TreeMapper();

        $tree = new Tree(-1, "Parent 1");
        $treeMapper->insert($tree);
    
        $tree = $treeMapper->find(1); 

        $ch1 = new Child(-1, 'Child 1 The Space Upstairs', $tree->getId());
        $ch1->setName('Child 1 The Space');
        
        $ch2 = new Child(-1, 'Child 3', $tree->getId());
        $ch3 = new Child(-1, 'Child 4', $tree->getId());
        $ch2->addChild($ch3);
        $ch1->addChild($ch2);

        $ch4 = new Child(-1, 'Child 5', $tree->getId());
        $ch1->addChild($ch4);

        $ch5 = new Child(-1, 'Child 6', $tree->getId());
        $ch1->addChild($ch5);

        $tree->addChild($ch1);
        //dump($tree);
        ObjectWatcher::instance()->performOperations();
        
        $treeMapper = new TreeMapper('categories', ['html' => false]);
        $tree = $treeMapper->getTree(1);
        return $tree;
    }

    public static function run2()
    {
        $config = __DIR__ . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['config']);
        $reg->setConf($conf);
        $reg = Registry::instance();
        $dsn = $reg->getDSN();

        if (is_null($dsn)) {
            throw new AppException("No DSN");
        }

        $treeMapper = new TreeMapper('categories',['html'=>false]);
        $cats = $treeMapper->getTree(1);
        dump($cats);

        $treeMapper = new TreeMapper('categories', ['html' => false, 'simpleArray'=>true]);
        $cats = $treeMapper->getTree(1);
        dump($cats);

    }

    public static function run3()
    {
        $config = __DIR__ . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['mysql']);
        $reg->setConf($conf);
        $reg = Registry::instance();
        $treeMapper = new TreeMapper('categories', ['html' => true]);
        echo $treeMapper->getTree(1);
    }

    public static function run4()
    {
        $config = __DIR__ . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['mysql']);
        $reg->setConf($conf);
        $reg = Registry::instance();
        $treeMapper = new TreeMapper();

        $tree = $treeMapper->find(1);
        dump($tree->getName());
        $tree->setName("Parent 1 Beer Likey Lounge");
        $treeMapper->update($tree);
        dump($tree);
    }

    public static function setUp()
    {
        $config = __DIR__ . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['mysql']);
        $reg->setConf($conf);
        $reg = Registry::instance();
        $dsn = $reg->getDSN();

        if (is_null($dsn)) {
            throw new AppException("No DSN");
        }
        
        if ($conf->get('driver') == 'mysql') {
            $username = $conf->get('username');
            $password = $conf->get('password');
        }
        $pdo = new \PDO($dsn, $username ?? null, $password ?? null);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $autoincrement = "AUTOINCREMENT";

        if ($conf->get('driver') == 'mysql') {
            $pdo->query("DROP TABLE IF EXISTS categories");
            $pdo->query("CREATE TABLE `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` varchar(64) DEFAULT NULL,
                `parent_id` int DEFAULT NULL,
                `lft` int DEFAULT NULL,
                `rgt` int DEFAULT NULL,
                `lvl` int DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
                 
        } else {
            $pdo->query("DROP TABLE IF EXISTS categories");
            $pdo->query("CREATE TABLE categories 
            ( id INTEGER PRIMARY KEY $autoincrement, name TEXT, parent_id INTEGER REFERENCES categories, left INTEGER, rgt INTEGER, lvl INTEGER)");
        }
       /*  $pdo->query("INSERT into categories ( name,parent_id,left, rgt,lvl ) values ('Parent', 0, 0, 1, 1)"); */
        
    }
}
