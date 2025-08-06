<?php
namespace Tree;
require_once(__DIR__ . "/vendor/autoload.php");
use Tree\Runner;

$runner = new Runner();
$result = $runner->run8();
dump($result);
?>