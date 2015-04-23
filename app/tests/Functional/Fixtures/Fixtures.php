<?php

namespace Tests\Functional\Fixtures;

use Models\Merchant;
use Config;
use Eloquent;
use Tests\TestDummy\Factory;
use Models;

class Fixtures
{
    protected static $instance = null;

    public $links = [];

    protected $times = 1;

    protected $defaultConn = 'test';

    public function __construct()
    {
        Entity\Base::$fixturesInstance = $this;

        $this->base = new Entity\Base;
    }

    public static function getInstance()
    {
        if (self::$instance === null)
        {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public function times($times)
    {
        $this->times = $times;

        return $this;
    }

    /**
     * Seed the db with required data
     */
    public function setUp()
    {
        $this->merchant->setUp();

        $apiMerchant = $this->create('merchant', ['id' => '1cXSLlUU8V9sXl', 'pricing_plan_id' => '1hDYlICobzOCYt']);
        $apiBalance = $this->base->create('balance', ['id' => '1cXSLlUU8V9sXl']);

        $this->base->connection('test');

        $this->create('pricing:default_plan');

        $entities = $this->create('merchant:default_test_merchant');

        $this->sharedTerminal = $this->create('terminal:shared_atom_terminal');

        $this->entities = $entities;
    }

    public function generateUniqueId()
    {
        return \Models\Base\UniqueIdEntity::generateUniqueId();
    }

    public function connection($mode = 'test')
    {
        Config::set('database.default', $mode);

        return $this;
    }

    public function on($mode)
    {
        $this->connection($mode);

        return $this;
    }

    public function create($resource, array $attributes = array())
    {
        list($obj, $method, $arg1, $arg2) = $this->getEntityMethodAndArgs($resource, $attributes);

        $times = $this->getTimes();
        $this->times = 1;

        $entities = [];

        while ($times--)
        {
            $entities[] = $obj->$method($arg1, $arg2);
        }

        return count($entities) > 1 ? $entities : $entities[0];
    }

    public function getTimes()
    {
        return $this->times;
    }

    protected function getEntityMethodAndArgs($resource, $attributes)
    {
        list($entity, $method) = $this->getEntityAndMethod($resource);

        $obj = null;

        $class = studly_case($entity);

        $class = __NAMESPACE__.'\Entity\\'.$class;

        $arg1 = $entity;
        $arg2 = $attributes;
        $obj = $this->base;

        if (class_exists($class) and $method !== 'create')
        {
            $obj = $this->getEntityFixtureInstance($class, $entity);

            $arg1 = $attributes;
            $arg2 = null;
        }

        return [$obj, $method, $arg1, $arg2];
    }

    protected function getEntityFixtureInstance($class, $entity)
    {
        if (class_exists($class))
        {
            if (isset($this->links[$entity]) === false)
            {
                $this->links[$entity] = new $class;
            }

            $obj = $this->links[$entity];

            return $obj;
        }
    }

    protected function getEntityAndMethod($resource)
    {
        $pair = explode(':', $resource);

        if (isset($pair[1]) === false)
        {
            $pair[1] = '';
        }

        $entity = $pair[0];
        $method = $pair[1];

        $obj = null;

        $method = 'create'.studly_case(ucfirst($method));

        return [$entity, $method];
    }

    public function __get($key)
    {
        if (isset($this->links[$key]))
        {
            return $this->links[$key];
        }
        else
        {
            $class = __NAMESPACE__.'\Entity\\'.studly_case($key);

            $obj = $this->getEntityFixtureInstance($class, $key);

            if ($obj === null)
                throw new \Exception($key . ' not found');

            return $obj;
        }
    }

    public function setDefaultConn($conn = '')
    {
        if ($conn === '')
            $conn = $this->defaultConn;
        else
            $this->defaultConn = $conn;

        $this->connection($this->defaultConn);
    }
}