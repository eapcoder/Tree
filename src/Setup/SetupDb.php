<?php
declare(strict_types=1);

namespace Tree\Setup;

use Tree\Child;
use Tree\Exception\AppException;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Mappers\TreeMapper;
use Tree\Tree;

class SetupDb {

    public static function setUp($type = 'mysql')
    {
        $config = dirname(__DIR__) . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options[$type] + $options['config']);
        $reg->setConf($conf);
        $dsn = $reg->getDSN();

        if (is_null($dsn)) {
            throw new AppException("No DSN");
        }
        
        if ($conf->get('driver') == 'mysql') {
            $username = $conf->get('username');
            $password = $conf->get('password');
        }
        
        
        $pdo = new \PDO($dsn, $username ?? null, $password ?? null, [\PDO::ATTR_PERSISTENT => true]);
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
            ( id INTEGER PRIMARY KEY $autoincrement, name TEXT, parent_id INTEGER REFERENCES categories, lft INTEGER, rgt INTEGER, lvl INTEGER)");
        }
      
        
    }

    public static function setSqlite()
    {
        $config = dirname(__DIR__) . "/data/options.ini";
        $options = parse_ini_file($config, true);
        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['sqlite']);
        $reg->setConf($conf);
    }

    protected static function setMysql()
    {
        $config = dirname(__DIR__) . "/data/options.ini";
        $options = parse_ini_file($config, true);
        $reg = Registry::instance();
        $conf = new Conf($options['mysql'] + $options['config']);
        $reg->setConf($conf);
         
    }

    public static function insertInitData()
    {
        $treeMapper = new TreeMapper();

        $tree = new Tree(-1, "Parent 1");
        $treeMapper->insert($tree);
        $tree = $treeMapper->find($tree->getId());

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
        //ObjectWatcher::instance()->performOperations();
        $tree->save();
    }


}
    ?>



