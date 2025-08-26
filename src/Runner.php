<?php
declare(strict_types=1);

namespace Tree;

use Tree\Mappers\ChildMapper;
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

        $tree = new Tree("Parent 1");
        $treeMapper->insert($tree);
        $tree = $treeMapper->find(1); 

        $ch1 = new Child('Child 1 The Space Upstairs');
        $ch1->setName('Child 1 The Space');
    
        $ch2 = new Child('Child 2');
        $ch21 = new Child('Child 2.1');
        $ch2->addChild($ch21);
        $ch22 = new Child('Child 2.2');
        $ch2->addChild($ch22);

        $ch23 = new Child('Child 2.3');
        $ch231 = new Child('Child 2.3.1');
        $ch23->addChild($ch231);
        $ch2->addChild($ch23);
        
        $ch1->addChild($ch2);

        $ch3 = new Child('Child 3');
        $ch31 = new Child('Child 3.1');
        $ch3->addChild($ch31);
        $ch32 = new Child('Child 3.2');
        $ch3->addChild($ch32);
        $ch33 = new Child('Child 3.3');
        $ch3->addChild($ch33);
        
        $ch1->addChild($ch3);

        $ch4 = new Child('Child 4');
        $ch41 = new Child('Child 4.1');
        $ch4->addChild($ch41);
        $ch42 = new Child('Child 4.2');
        $ch4->addChild($ch42);
       
        $ch1->addChild($ch4);

        $tree->addChild($ch1);

        $ch5 = new Child('Child 5. The End');
        $tree->addChild($ch5);

        $tree->save();
      

       
   
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
      
        $new = new Child('Child 2.3.1.1');
        $tree->addChild($new);
        $tree->save();

        $tree = $treeMapper->find(3);
        $new = new Child('Child 2.4');
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
        self::setMysql();
        $treeMapper = new TreeMapper();
        $treeMapper->find(16)->remove();
        
    }

    /**
     * Find element and move it up to next level
     */
    public static function run10()
    {
        self::run();
        self::run5();
       
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(18);
        $tree->moveLevelUp();

    }

    /**
     * Find tree element and move it up in one level
     */
    public static function run11()
    {
        self::run();
        //self::setMysql();
        self::run5();
        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(6);
        $tree->moveUp();
        $tree->moveUp();

        $four = $treeMapper->find(4);
       
        $tree = $four->moveDown();
        $tree = $four->moveDown();

        ObjectWatcher::instance()->ClearNew();
        $childMapper = new ChildMapper();
        $child = $childMapper->find(4); 
        
        //dump(ObjectWatcher::instance()->getNew());
        $four->addChildTest($child);
        $four->addChildTest($child);
       
        $new = $four->update();
        
    }


    /**
     * Retrieving a Single Path
     */
    public static function run12()
    {
        //self::run();
        self::setMysql();
        //self::run5();
        $treeMapper = new TreeMapper();
        $path = $treeMapper->getSinglePath('2.3.1.1');
        dump($path);
    }

    /**
     * Finding the Depth of the Nodes
     */
    public static function run13()
    {
        //self::run();
        self::setMysql();
        //self::run5();
        $treeMapper = new TreeMapper();
        $path = $treeMapper->getPathWithDepth(15);
        dump($path);
    }

    /**
     * Find the immediate subordinates of a node
     */
    public static function run14()
    {
        //self::run();
        self::setMysql();
        //self::run5();
        $treeMapper = new TreeMapper();
        $path = $treeMapper->selectImmediateSubElementStmt('Parent 1');
        dump($path);
    }

    /**
     * Find child copy and insert after some node
     */
    public static function run15()
    {
        self::run();
        self::run5();

        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(4);

        $new = new Child('Child after 2.4');
        $after = new Child('Child after after 2.4');
        $after2 = new Child('Child after after after 2.4');
        $after3 = new Child('Child after after after 3');
        $after4 = new Child('Child after after after 4');
        $after5 = new Child('Child after after after 5');
        $after->addChild($after2);
        $after->addChild($after3);
        $after->addChild($after4);
        $after->addChild($after5);
        $new->addChild($after);

        $tree->insertAfter($new, 5);

    }

 
}

