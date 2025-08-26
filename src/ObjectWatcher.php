<?php
declare(strict_types=1);

namespace Tree;

use Tree\Conf\Registry;
use Tree\Helpers\TreeRebuilder;

class ObjectWatcher
{
    use TreeRebuilder;
    
    private array $all = [];
    private array $dirty = [];
    private array $new = [];
    private array $delete = []; // unused in this example
    private static ?ObjectWatcher $instance = null;

    private function __construct() {}

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new ObjectWatcher();
        }

        return self::$instance;
    }

    public function globalKey(DomainObject $obj): string
    {
        return  get_class($obj) . "." . $obj->getId();
    }

    public static function add(DomainObject $obj): DomainObject
    {
        $inst = self::instance();
        $inst->all[$inst->globalKey($obj)] = $obj;

        return $obj;
    }


    public static function ClearAll(): ?array
    {
        $inst = self::instance();
        return $inst->all = [];
    }

    public static function all(): ?array
    {
        $inst = self::instance();
        
        return $inst->all;
    }

    public static function getNew(): ?array
    {
        $inst = self::instance();

        return $inst->new;
    }
    public static function ClearNew(): ?array
    {
        $inst = self::instance();
        return $inst->new = [];
    }

    public static function exists($classname, $id): ?DomainObject
    {
        $inst = self::instance();
        $key = "{$classname}.{$id}";
       
        if (isset($inst->all[$key])) {
            return $inst->all[$key];
        }

        return null;
    }

    public static function addDelete(DomainObject $obj): void
    {
        $inst = self::instance();
        $inst->delete[$inst->globalKey($obj)] = $obj;
    }

    public static function addDirty(DomainObject $obj): void
    {
        $inst = self::instance();
     
        if (! in_array($obj, $inst->new, true)) {
            $inst->dirty[$inst->globalKey($obj)] = $obj;
        }
    }

    public static function addNew(DomainObject $obj): void
    {
        $inst = self::instance();
        // we don't yet have an id
        $inst->new[] = $obj;
    }

    public static function addClean(DomainObject $obj): void
    {
        $inst = self::instance();
        unset($inst->delete[$inst->globalKey($obj)]);
        unset($inst->dirty[$inst->globalKey($obj)]);

        $inst->new = array_filter(
            $inst->new,
            function ($a) use ($obj) {
                return !($a === $obj);
            }
        );
    }
    /**
     * Main function to insert child in tree structure
     * $parent - only for update existing tree element
     * @return void
     */
    public function performOperations($parent = null): array
    {
        $return = [];
       
        foreach ($this->dirty as $key => $obj) {
            $obj->getFinder()->update($obj);
        }
        
      
        $fitstId = $this->new[0]->getId() ?? null;
       
      
        foreach($this->new as $key => $obj) {
            
            if ($obj instanceof Tree) {
               
                $this->performOperationsForChilds($obj->getChilds(), $lvl = 1, $obj->getId());
                
            } else {
               
                if ($obj->getId() <= 0)
                $this->performOperationsForChilds($obj, $lvl = 1, $parent ?? 1);
                /* if ($obj->hasChilds()) {
                    $this->performOperationsForChilds($obj->getChilds(), $lvl = 1, $obj->getParent());

                } */
            }
            if ($obj->hasChilds()) {
                //$this->performOperationsForChilds($obj->getChilds(), $lvl = 1);

            } else {
            }

            $reg = Registry ::instance();
            if($reg->getConf()->get('dev')) print "inserting " . $obj->getName() . "\n";
            array_push($return, $obj);
        }
        
        //$this->rebuild($fitstId);
        $this->dirty = [];
        $this->new = [];
        ObjectWatcher::ClearAll();
        return $return;
    }
    

    public function performOperationsForChilds($childs, $lvl, $parent_id): void
    {
        if ($childs instanceof Child) {
           
            if(!$childs->isExist()) {
                
                $lvl = $lvl + 1;
                //if($childs->rebuild) $childs->setId($childs->rebuild);
                $childs->setLvl($lvl);
      
                $childs->setParent($parent_id);
                $childs->getFinder()->insert($childs);
            } else {
          
               
            }
            
            if($childs->hasChilds()) $this->performOperationsForChilds($childs->getChilds(), $lvl, $childs->getId());
        } else { // Tree\Collection\ChildCollection
            
            foreach ($childs as $child) {
                //dump($child->getChilds());
                $this->performOperationsForChilds($child, $lvl, $parent_id);
            }
        }
        

    }

    protected function rebuild($fitstId): void
    {
        $tree = $this->new[0]->getFinder()->getTree($fitstId);

        if ($tree->hasChilds()) {
            $this->generate($tree);
        }
    }
}
