<?php

namespace Tree\Conf;

use Tree\EventMapper;
use Tree\Mappers\ChildMapper;
use Tree\Mappers\TreeMapper;

abstract class MappManager
{
    abstract public function getChildMapper(): ChildMapper;
    abstract public function getEventMapper(): EventMapper;
    abstract public function getTreeMapper(): TreeMapper;
    
}

