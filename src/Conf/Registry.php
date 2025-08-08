<?php

declare(strict_types=1);

namespace Tree\Conf;


use Tree\Conf\Request;
use Tree\ApplicationHelper;
use Tree\Collection\ChildCollection;
use Tree\Collection\EventCollection;
use Tree\Collection\TreeCollection;
use Tree\Exception\AppException;
use Tree\Mappers\ChildMapper;
use Tree\Mappers\TreeMapper;

class Registry extends FactoryMapper
{
    private static $instance = null;
    private ?Request $request = null;
    private ?Conf $conf = null;
    private ?Conf $commands = null;
    private ?\PDO $pdo = null;

    private function __construct()
    {
    }

    public static function instance() : self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    // must be initialized by some smarter component
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        if (is_null($this->request)) {
            throw new \Exception("No Request set");
        }

        return $this->request;
    }

    public function getApplicationHelper(): ApplicationHelper
    {
        // could persist an instance if needed
        return new ApplicationHelper();
    }

    public function setConf(Conf $conf): void
    {
        $this->conf = $conf;
    }

    public function getConf(): Conf
    {
        if (is_null($this->conf)) {
            $this->conf = new Conf();
        }

        return $this->conf;
    }

    public function setCommands(Conf $commands): void
    {
        $this->commands = $commands;
    }

    public function getCommands(): Conf
    {
        return $this->commands;
    }

    public function getDSN(): string
    {
        $conf = $this->getConf();
        
        if($conf->get('driver') == 'mysql') {
            return $this->getMysqlDsn();
        } else {
            return $conf->get("dsn");
        }
        
    }

    public function getPdo(): \PDO
    {
        if (is_null($this->pdo)) {
            $dsn = $this->getDSN();

            if (is_null($dsn)) {
                throw new AppException("No DSN");
            }
            $conf = $this->getConf();
            if ($conf->get('driver') == 'mysql') {
                $username = $conf->get('username');
                $password = $conf->get('password');
            }

            $this->pdo = new \PDO($dsn, $username ?? null, $password ?? null);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->pdo;
    }

    public function getMysqlDsn() {

        $conf = $this->getConf();
       
        $dsn = $conf->get('driver') . ':host=' . $conf->get('host') . 
        ((!empty($conf->get('port'))) ? (';port=' . $conf->get('port')) : '') .
            ';dbname=' . $conf->get('schema');

        return $dsn;

    }


    public function getTreeCollection(): TreeCollection
    {
        return new TreeCollection();
    }

    public function getChildCollection(): ChildCollection
    {
        return new ChildCollection();
    }

    //TODO delete?
    public function getEventCollection(): EventCollection
    {
        return new EventCollection();
    }
}
