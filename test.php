<?php

namespace Tree;

require_once(__DIR__ . "/vendor/autoload.php");
require_once(__DIR__ . "/src/Helpers/Functions.php");

use Tree\Runner;

addHtml();

$result = Runner::run15();
$result = Runner::run6();
