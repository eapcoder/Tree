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
    
        $ch2 = new Child(-1, 'Child 2', $tree->getId());
        $ch21 = new Child(-1, 'Child 2.1', $tree->getId());
        $ch2->addChild($ch21);
        $ch22 = new Child(-1, 'Child 2.2', $tree->getId());
        $ch2->addChild($ch22);

        $ch23 = new Child(-1, 'Child 2.3', $tree->getId());
        $ch231 = new Child(-1, 'Child 2.3.1', $tree->getId());
        $ch23->addChild($ch231);
        $ch2->addChild($ch23);
        
        $ch1->addChild($ch2);

        $ch3 = new Child(-1, 'Child 3', $tree->getId());
        $ch31 = new Child(-1, 'Child 3.1', $tree->getId());
        $ch3->addChild($ch31);
        $ch32 = new Child(-1, 'Child 3.2', $tree->getId());
        $ch3->addChild($ch32);
        $ch33 = new Child(-1, 'Child 3.3', $tree->getId());
        $ch3->addChild($ch33);
        
        $ch1->addChild($ch3);

        $ch4 = new Child(-1, 'Child 4', $tree->getId());
        $ch41 = new Child(-1, 'Child 4.1', $tree->getId());
        $ch4->addChild($ch41);
        $ch42 = new Child(-1, 'Child 4.2', $tree->getId());
        $ch4->addChild($ch42);
       
        $ch1->addChild($ch4);

        $tree->addChild($ch1);
        
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
       
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(7);
      
        $new = new Child(-1, '2.3.1.1', $tree->getId());
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
        $tree = $treeMapper->find(4);
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

    /**
     * Find tree element and remove it
     */
    public static function run9()
    {
        
        self::run5();

        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(3);
        dump($tree);

        $tree->remove();
    }

    /**
     * Find tree element and remove it
     */
    public static function run10()
    {

        self::run();
        self::run5();
       
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(15);
        $tree->moveLevelUp();

    }

    /**
     * Find tree element and remove it
     */
    public static function run11()
    {
        //self::run();
        //self::run5();
        self::setMysql();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(5);
        $tree->moveUp();
    }

}

