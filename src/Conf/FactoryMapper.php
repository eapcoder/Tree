<?php

namespace Tree\Conf;

use Tree\EventMapper;
use Tree\Mappers\ChildMapper;
use Tree\Mappers\TreeMapper;

class FactoryMapper extends MappManager
{
    public function getTreeMapper(): TreeMapper
    {
        return new TreeMapper();
    }

    public function getChildMapper(): ChildMapper
    {
        return new ChildMapper();
    }
    //TODO delete?
    public function getEventMapper(): EventMapper
    {
        return new EventMapper();
    }
}
