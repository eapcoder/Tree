<?php

declare(strict_types=1);

namespace Tree\Helpers;

use Tree\Child;
use Tree\Conf\Conf;
use Tree\Conf\Registry;
use Tree\Exception\AppException;
use Tree\Mappers\Mapper;
use Tree\Tree;


trait ExperimentalFunctions
{
   
    public ?bool $withRoot = false;
    public static ?string $html = '';
    protected \PDO $pdo;

    public function init()
    {
        $config = dirname(__DIR__) . "/data/options.ini";
        $options = parse_ini_file($config, true);
        $conf = new Conf($options['mysql']);
        Registry::reset();
        $reg = Registry::instance();
       
        $reg->setConf($conf);
        $dsn = $reg->getDSN();
       
        if (is_null($dsn)) {
            throw new AppException("No DSN");
        }

        if ($conf->get('driver') == 'mysql') {
            $username = $conf->get('username');
            $password = $conf->get('password');
        }


        $this->pdo = new \PDO($dsn, $username ?? null, $password ?? null, [\PDO::ATTR_PERSISTENT => true]);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        //$this->pdo = $reg->getMyPdo();
    }


    function interpolateQuery($query, $params)
    {
        $keys = array();
        $values = $params;

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                // Named parameter
                $keys[] = '/:' . $key . '/';
            } else {
                // Positional parameter
                $keys[] = '/[?]/';
            }

            // Quote string values for safe interpolation in the debug output
            if (is_string($value)) {
                $values[$key] = "'" . str_replace("'", "''", $value) . "'";
            } elseif (is_array($value)) {
                // Handle array values for IN clauses, etc.
                $quotedValues = array_map(function ($val) {
                    return "'" . str_replace("'", "''", $val) . "'";
                }, $value);
                $values[$key] = implode(",", $quotedValues);
            } elseif (is_null($value)) {
                $values[$key] = 'NULL';
            }
            // Numeric values don't require quoting
        }

        $query = preg_replace($keys, $values, $query, 1); // Limit to 1 replacement for positional placeholders
        return $query;
    }
    

    /**
     * Experimental function 
     * 
     */

    public function generate($tree = null, &$left = 0, &$right = 0, &$t = null, &$rightParent = null, &$lastlvl = null, $UpLevel = null)
    {
        //trace();
        $level = $level ?? 0;

        $common = $common ?? [];
        if ($tree instanceof Tree) {

            $left = $right = 1;
            $tree->setLeft($left);
            $tree->setRight($this->getTotal($tree->getId()) * 2);
            ++$level;
            //dump($tree);
            //dump($this->getTotal($tree->getId()));
        }

        $down = 0;
        foreach ($tree->getChilds() as $key => $childs) {
            if (!$UpLevel) {
                $left = $right = $right + 1;
            } else {
                $left = $left + 1;
                $UpLevel = false;
            }
            $up = (($lastlvl <=> $childs->getLvl()) == 1);

            //dump($up);
            //dump($down);
            if ($childs->hasChilds()) {

                $rightParent = $this->getTotal($childs->getId()) * 2 + $left - (($up) ? 0 : 1);
                $childs->setRight($rightParent);

                if ($up) { // 1
                    $left  = $left + 1;
                    $right = $rightParent;
                    $UpLevel = true;
                }
            } else {
                if ($up) { // 1
                    $left  = $rightParent + 1;
                }
                $right = $left + 1;
                $childs->setRight($right);
            }

            $lastlvl = $childs->getLvl();
            $childs->setLeft($left);

            //dump('$left = ' . $left . ' $right = ' . $right);
            //dump($childs);

            if ($childs->hasChilds()) {

                $this->generate($childs, $left, $right, $t, $rightParent, $lastlvl, $UpLevel);
            }
        }
    }
}
