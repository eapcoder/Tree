<?php

function trace()
{
    $e = new \Exception;
    if(function_exists('dump')) {
        dump($e->getTraceAsString());
    } else {
        var_dump($e->getTraceAsString());
    }
}

function addHtml()
{
    echo '<html><head><style>body { background-color:#000; color:#fff;} span.var {border:1px solid #cececeff; border-radius:3px; background-color:#7b14f0ff;padding:3px;}</style></html>';
}
?>