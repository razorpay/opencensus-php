<?php

namespace Tests\Functional\Fixtures\Entity;

use Config;
use Eloquent;
use Models;
use Gateway;
use Tests\TestDummy\Factory;
use Tests\Functional\Fixtures\Fixtures;
use Illuminate\Support\Facades\DB;

class Base
{
    public static $fixturesInstance;

    public function __construct()
    {
        $this->fixtures = self::$fixturesInstance;

        $this->db = DB::getFacadeRoot();
    }

    protected static $map = array(
        'key'           => Models\Key\Entity::class,
        'iin'           => Models\Card\IIN\Entity::class,
        'atom'          => Gateway\Atom\Entity::class,
        'card'          => Models\Card\Entity::class,
        'hdfc'          => Gateway\Hdfc\Entity::class,
        'user'          => Models\User\Entity::class,
        'order'         => Models\Order\Entity::class,
        'refund'        => Models\Payment\Refund\Entity::class,
        'webhook'       => Models\Merchant\Webhook\Entity::class,
        'methods'       => Models\Merchant\Methods\Entity::class,
        'balance'       => Models\Merchant\Balance\Entity::class,
        'methods'       => Models\Merchant\Methods\Entity::class,
        'payment'       => Models\Payment\Entity::class,
        'pricing'       => Models\Pricing\Entity::class,
        'webhook'       => Models\Merchant\Webhook\Entity::class,
        'merchant'      => Models\Merchant\Entity::class,
        'terminal'      => Models\Terminal\Entity::class,
        'adjustment'    => Models\Adjustment\Entity::class,
        'settlement'    => Models\Settlement\Entity::class,
        'transaction'   => Models\Transaction\Entity::class,
        'bank_account'  => Models\Merchant\BankAccount\Entity::class,
        'emi_plan'      => Models\Emi\Entity::class
    );

    protected static $liveAndTest = array(
        'merchant',
        'pricing',
        'methods',
        'emi_plan',
        'iin'
    );

    public function create(array $attributes = array())
    {
        $entity = snake_case(explode('\\', get_class($this))[4]);

        return $this->createEntity($entity, $attributes);
    }

    public function createEntity($entity, array $attributes = array())
    {
        if (in_array($entity, self::$liveAndTest))
        {
            return $this->createEntityInTestAndLive($entity, $attributes);
        }

        return $this->save($entity, $attributes);
    }

    public function edit($id, array $attributes = array())
    {
        $entity = snake_case(explode('\\', get_class($this))[4]);

        return $this->editEntity($entity, $id, $attributes);
    }

    public function editEntity($entity, $id, array $attributes = array())
    {
        $this->stripSign($id);

        if (in_array($entity, self::$liveAndTest))
        {
            return $this->editEntityInTestAndLive($entity, $id, $attributes);
        }

        $entity = self::$map[$entity];
        $entity = $entity::findOrFail($id);

        foreach ($attributes as $key => $value)
        {
            $entity[$key] = $value;
        }

        $entity->saveOrFail();

        return $entity;
    }

    public function createEntityInTestAndLive($entity, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$map[$entity];

        $entity = Factory::build($entity, $attributes);

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->saveOrFail();
        $liveEntity->setConnection('live')->saveOrFail();

        $entity->exists = true;
        $entity->setRawAttributes($liveEntity->getAttributes(), true);

        $this->eloquentReguard();

        $this->fixtures->setDefaultConn();

        return $entity;
    }

    public function editEntityInTestAndLive($entity, $id, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$map[$entity];
        $entity = $entity::findOrFail($id);

        foreach ($attributes as $key => $value)
        {
            $entity[$key] = $value;
        }

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->saveOrFail();
        $liveEntity->setConnection('live')->saveOrFail();

        $entity->setRawAttributes($liveEntity->getAttributes(), true);

        $this->eloquentReguard();

        $this->fixtures->setDefaultConn();

        return $entity;
    }

    public function build($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entity = self::$map[$entity];

        $entity = Factory::build($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function save($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entityClass = self::$map[$entity];

        $entity = Factory::create($entityClass, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function transaction(callable $callable)
    {
        $db = \DB::getFacadeRoot();

        return $db->transaction($callable);
    }

    protected function callInTransaction($callable, $args)
    {
        return $this->db->transaction(function ()
        {
            return call_user_func($callable);
        });
    }

    protected function stripSign(& $id)
    {
        $ix = strpos($id, '_');

        if ($ix !== false)
        {
            $id = substr($id, $ix + 1);
        }
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

    public function onLive()
    {
        $this->on('live');

        return $this;
    }

    public function onTest()
    {
        $this->on('test');

        return $this;
    }

    protected function getNamespace()
    {
        return substr(get_called_class(), 0, strrpos(get_called_class(), '\\'));
    }
}
