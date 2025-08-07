<?php
declare(strict_types=1);

namespace Tree;

use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\TreeMapper;
use Tree\Setup\SetupDb;

class Runner extends SetupDb
{
    public static function run()
    {
        self::setUp('mysql');
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
        
        //ObjectWatcher::instance()->performOperations();
        $tree->save();
      
        $treeMapper = new TreeMapper('categories', ['simpleArray' => true]);
        $tree = $treeMapper->getTree(1);
        dump($tree);
        return $tree;
    }

    
    public static function run5()
    {
        self::setMysql();
        $treeMapper = new TreeMapper('categories', ['html' => true]);
        $tree = $treeMapper->find(1);
        dump($tree);

    }

    public static function run8()
    {
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->findBy(['name'=> 'Child 1 The Space']);
        dump($tree);
        return $tree;
        
    }


    public static function run7()
    {
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(2);
        //dump($tree->getChilds());
        foreach ($tree->getChilds() as $item) {
            dump($item->getTree());
        }
    }

  
    public static function run6()
    {

        self::setSqlite();
        $treeMapper = new TreeMapper('categories',['html'=>true]);
        $tree = $treeMapper->getTree(2);
        return $tree;
    }

    public static function run2()
    {
        self::setSqlite();

        $treeMapper = new TreeMapper('categories',['html'=>false]);
        $cats = $treeMapper->getTree(1);
        dump($cats);

        $treeMapper = new TreeMapper('categories', ['html' => false, 'simpleArray'=>true]);
        $cats = $treeMapper->getTree(1);
        dump($cats);

    }

    public static function run3()
    {
        self::setSqlite();
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

}
