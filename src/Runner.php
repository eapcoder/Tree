<?php
declare(strict_types=1);

namespace Tree;
use Tree\Mappers\TreeMapper;
use Tree\Setup\SetupDb;

/**
 * Class for test Tree function
 */

class Runner extends SetupDb
{
    /**
     * Added tree with childs as initial data to sql
     */

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


    /**
     * Tree from Sqlite
     */
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

    /**
     * Output tree in html format from Sqlite
     */
    public static function run3()
    {
        self::setSqlite();
        $treeMapper = new TreeMapper('categories', ['html' => true]);
        echo $treeMapper->getTree(1);
    }

    /**
     * Find element in tree and update it
     */
    public static function run4()
    {
        self::setMysql();
        $treeMapper = new TreeMapper('categories', ['html' => true]);
        $tree = $treeMapper->find(1);
        dump($tree->getName());
        $tree->setName("Parent 1 Beer Likey Lounge");
        $treeMapper->update($tree);
        dump($tree);
    }


    /**
     * Added sub tree with childs to child
     */
    public static function run5()
    {
        self::run();
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(4);
        dump($tree);
        dump($tree->getId());
        $new = new Child(-1, 'New Child', $tree->getId());

        $subchild = new Child(-1, 'New Sub child', $tree->getId());
        $new->addChild($subchild);
        $new->setName('New Child space');
        $tree->addChild($new);
        $tree->save();
    }

    /**
     * Output html tree
     */

    public static function run6()
    {
        self::setMysql();
        $treeMapper = new TreeMapper('categories', ['html' => true]);
        $tree = $treeMapper->getTree();
        echo $tree;
    }

    /**
     * Find tree element by title and parent with some id
     */

    public static function run7()
    {
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(2);
        $tree->up();
        dump($tree);
        
    }

    /**
     * Find tree element by title
     */

    public static function run8()
    {
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->findBy(['name' => 'Child 1 The Space']);
        dump($tree);
        return $tree;
    }


}
