<?php
namespace Tree;
require_once(__DIR__ . "/vendor/autoload.php");
require_once(__DIR__ . "/src/Helpers/Functions.php");
use Tree\Runner;

echo '<html><head><style>body { background-color:#000; color:#fff;}</style></html>';
$result = Runner::run();

$result = Runner::run6();

?>