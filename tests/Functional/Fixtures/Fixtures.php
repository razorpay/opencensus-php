<?php

namespace RZP\Tests\Functional\Fixtures;

use Config;
use Eloquent;
use RZP\Models;
use Illuminate\Support\Facades\Artisan;

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
        $this->base->connection('test');

        $this->org->setUp();

        $this->merchant->setUp();

        $merchantData = [
            'id'                    => '1cXSLlUU8V9sXl',
            'pricing_plan_id'       => '1hDYlICobzOCYt',
            'org_id'                => '100000razorpay',
            'international'         => 1,
            'product_international' => '1111000000',
        ];

        $apiMerchant = $this->create('merchant', $merchantData);
        $apiBalance = $this->create('balance', ['id' => '1cXSLlUU8V9sXl', 'merchant_id' => '1cXSLlUU8V9sXl']);

        $this->create('pricing:default_plan');
        $this->create('pricing:zero_pricing_plan');
        $this->create('pricing:default_banking_plan');
        $this->create('pricing:default_commission_plan');
        $this->create('pricing:default_partner_commission_plan');
        $this->create('pricing:default_plan_for_submerchants_of_onboarded_partners');

        $this->create('org:default_test_organization');

        $entities = $this->create('merchant:default_test_merchant');

        $this->create('iin:default_iins');

        $this->create('card:default_cards');

        $this->customer->setUp();

        $this->create('device', [
            'id' => 'RazorpayDevice',
            'verification_token' => 'sample_verification_token',
            'auth_token' => 'authentication_token',
        ]);

        $this->create('vpa:default');

        $this->workflow->setUp();

        $this->user->setUp();

        $this->entities = $entities;

        $this->seedP2pFixture();

        $this->seedCacDataFixture();

        $this->create('gateway_rule:hitachi');
    }

    public function createEsIndex($entity, $mode)
    {
        Artisan::call(
            'rzp:index_create',
            [
                'mode'         => $mode,
                'entity'       => $entity,
                'index_prefix' => env('ES_ENTITY_TYPE_PREFIX'),
                'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                '--reindex'    => true,
            ]
        );
    }

    public function generateUniqueId()
    {
        return \RZP\Models\Base\UniqueIdEntity::generateUniqueId();
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
        list($obj, $method, $entity) = $this->getEntityMethodAndArgs($resource, 'create');

        $times = $this->getTimes();
        $this->times = 1;

        $entities = [];

        $arg1 = $arg2 = null;

        if ($entity === null)
        {
            $arg1 = $attributes;
        }
        else
        {
            $arg1 = $entity;
            $arg2 = $attributes;
        }

        while ($times--)
        {
            $entities[] = $obj->$method($arg1, $arg2);
        }

        return count($entities) > 1 ? $entities : $entities[0];
    }

    public function edit($resource, $id, array $attributes = array())
    {
        list($obj, $method, $entity) = $this->getEntityMethodAndArgs($resource, 'edit');

        $arg1 = $arg2 = $arg3 = null;
        $arg1 = $entity;

        if ($entity === null)
        {
            $arg1 = $id;
            $arg2 = $attributes;
        }
        else
        {
            $arg2 = $id;
            $arg3 = $attributes;
        }

        return $obj->$method($arg1, $arg2, $arg3);
    }

    public function getTimes()
    {
        return $this->times;
    }

    protected function getEntityMethodAndArgs($resource, $action)
    {
        list($entity, $method) = $this->getEntityAndMethod($resource, $action);

        $class = __NAMESPACE__.'\Entity\\' . studly_case($entity);

        $obj = $this->getEntityFixtureInstance($class, $entity);

        if (class_exists($class))
        {
            $entity = null;
        }
        else
        {
            $method .= 'Entity';
        }

        return [$obj, $method, $entity];
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

        return $this->base;
    }

    protected function getEntityAndMethod($resource, $action)
    {
        $pair = explode(':', $resource);

        if (isset($pair[1]) === false)
        {
            $pair[1] = '';
        }

        $entity = $pair[0];
        $method = $pair[1];

        $method = $action.studly_case(ucfirst($method));

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

    public function stripSign(& $id)
    {
        $ix = strpos($id, '_');

        if ($ix !== false)
        {
            $id = substr($id, $ix + 1);
        }
    }

    /**
     * Currently, we are using database seed to set P2P Fixture,
     * Later if required we can spit the data between seeder and fixture.
     */
    protected function seedP2pFixture()
    {
        \Artisan::call('db:seed', ['--class' => 'P2pSeeder']);
    }

    protected function seedCacDataFixture()
    {
        \Artisan::call('db:seed', array_filter([
            '--database' => 'live',
            '--class' => 'CACStaticDataSeeder',
        ]));
        \Artisan::call('db:seed', array_filter([
            '--database' => 'test',
            '--class' => 'CACStaticDataSeeder',
        ]));
    }
}
