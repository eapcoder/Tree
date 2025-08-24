<?php

namespace Tree\TestProject;



use PHPUnit\Framework\TestCase;
use Tree\Child;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\TreeMapper;
use Tree\Runner;
use Tree\Setup\SetupDb;
use Tree\Tree;


final class RunnerTest extends TestCase
{

    private $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new Runner();

    }

    protected function forRunSetUp(): void
    {
     
        // Initialize an in-memory SQLite database

        $config = "./src/data/options.ini";
        $options = parse_ini_file($config, true);

        Registry::reset();
        $reg = Registry::instance();
        $conf = new Conf($options['sqlite']);
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


        $pdo = new \PDO('sqlite::memory:');
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
    }



    public function testRun()
    {
        
        SetupDb::setUp('mysql');
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

        $treeMapper = new TreeMapper('categories', ['simpleArray' => true]);
        $tree = $treeMapper->getTree(1);

        $this->assertIsArray($tree);
        
    }


    public function testRun8() {

        SetupDb::setUp('mysql');
        SetupDb::insertInitData();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->findBy(['name' => 'Child 1 The Space']);

        $this->assertIsObject($tree);
    }

    public function testrun6()
    {
        
        $this->assertEquals('<ul><li>Child 1 The Space</li><ul><li>Child 3</li><ul><li>Child 4</li></ul><li>Child 5</li><li>Child 6</li></ul></ul>', $this->object::run6());
        //$this->assertTrue(true);
        //return 'second';
    }
}
