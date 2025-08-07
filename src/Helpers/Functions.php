<?php

function stack_trace()
{
    $e = new \Exception;
    if(function_exists('dump')) {
        dump($e->getTraceAsString());
    } else {
        var_dump($e->getTraceAsString());
    }
}


?>