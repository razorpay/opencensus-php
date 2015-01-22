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

    protected $links = [];

    protected $times = 0;

    public function __construct()
    {
        $this->base = new Entity\Base;
        Entity\Base::$fixturesInstance = $this;
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
        $this->create('merchant:nodal_account');

        $apiMerchant = $this->create('merchant', ['id' => '1cXSLlUU8V9sXl', 'pricing_plan_id' => '1hDYlICobzOCYt']);
        $apiBalance = $this->base->createEntityInTestAndLive('balance', ['id' => '1cXSLlUU8V9sXl']);

        $this->base->connection('test');

        $this->create('pricing:default_plan');

        $entities = $this->create('merchant:default_test_merchant');

        $this->on('test')->create(
                            'payment',
                            ['merchant_id' => '10000000000000',
                             'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->entities = $entities;
    }

    public function createPaymentAuthorizedEntity(array $attributes = array())
    {
        return $this->create('payment:authorized');
    }

    public function createTerminalEntityForAtomGateway(array $attributes = array())
    {
        $attributes = array(
            'merchant_id' => '10000000000000',
            'gateway' => 'atom',
            'gateway_merchant_id' => 'abcd',
            'gateway_terminal_id' => 'abcde',
            'gateway_terminal_password' => 'abcdef');

        return $this->create('terminal', $attributes);
    }

    protected function createEntityInTestAndLive($entity, $attributes = array())
    {
        return $this->base->createEntityInTestAndLive($entity, $attributes);
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

    protected function eloquentUnguard()
    {
        Eloquent::unguard();

        return $this;
    }

    protected function eloquentReguard()
    {
        Eloquent::reguard();

        return $this;
    }

    public function create($resource, array $attributes = array())
    {
        list($entity, $method) = $this->getEntityAndMethodFromCreate($resource);

        $obj = null;

        $class = __NAMESPACE__.'\Entity\\'.ucfirst($entity);

        if (class_exists($class) and $method !== 'create')
        {
            if (isset($this->links[$entity]) === false)
            {
                $this->links[$entity] = new $class;
            }

            $obj = $this->links[$entity];

            return $obj->$method($attributes);
        }

        $obj = $this->base;
        $method = 'create';

        $data = $obj->$method($entity, $attributes);

        return $data;
    }

    protected function getEntityAndMethodFromCreate($resource)
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
}