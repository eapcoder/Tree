# Tree
Tree nested lists
# Description
PHP class for build tree nested list and get it from sql table. Defaut table named `categories` 🚀.
# Requireds
8.0 - MySQL, SQLite3
Tested in SQLite, Mysql
## Setup
```bash
composer install
```
## How to use
### Configuration in /data/options.ini
1. Set dsn in data/options.ini mysql, sqlite.

```php
[config]
dev=true

[sqlite]
dsn=sqlite:/var/www/tree/src/data/woo.db


[mysql]
driver = mysql
host = treedatabase
port = 3306
schema = tree
username = root
password = my-secret-pw

```

2. Run index.php
See Runner class.
First step, test data to inserts to table categories different which driver is used (mysql, sqlite)

```php
$runner = new Runner();
$result = $runner::run();
dump($result);
```

3. Other examples.

```php
$runner = new Runner();
$result = $runner::run2();
dump($result);
```

Output in html format ul > li ....

```php
$runner = new Runner();
$result = $runner::run3();
dump($result);
```


```php
$runner = new Runner();
$result = $runner::run4();
dump($result);
```

## Output Html element
For output html markup use this example:
```php

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
```

## Moving element
File Runner contain examples moving child element (example 10 and 11):

```php
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
        self::run(); // add initial data
        self::run5(); // for test purpose
        self::setMysql();

        $treeMapper = new TreeMapper();
        $tree = $treeMapper->find(6);
        $tree->setName("Now child 2.2");
        $treeMapper->update($tree);
        $tree->moveUp();

        $tree = $treeMapper->find(20);
        $tree->setName("Now child 2.3");
        $treeMapper->update($tree);

       /*  
        $tree = $treeMapper->find(18);
        $tree->moveLevelUp(); 
        */
    }
```

## Moving element
Find child and insert after some node (example 15):

```php

    /**
     * Find child and insert after some node
     */
    public static function run15()
    {
        self::run();
        self::run5();

        $treeMapper = new TreeMapper();
        $old = $treeMapper->find(6);
        
           
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
        $treeMapper->insertAfter($new, 4);

     
        $treeMapper->insertAfter($old, 4);
        dump($old);
        $old->remove();
    }
```