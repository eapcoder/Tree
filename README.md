# Tree
Tree nested lists
# Description
PHP class for build tree nested list and get it
# Requireds
Tested in SQLite, Mysql
## Setup
```bash
composer install
```
## How to use
Run index.php

First step, test data to inserts to table categories different which driver is used (mysql, sqlite)

```php
$runner = new Runner();
$result = $runner::run();
dump($result);
```

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

