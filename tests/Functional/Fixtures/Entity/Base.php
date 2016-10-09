<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Config;
use Eloquent;
use RZP\Models;
use RZP\Tests\TestDummy\Factory;
use RZP\Tests\Functional\Fixtures\Fixtures;
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
        'key'           => \RZP\Models\Key\Entity::class,
        'iin'           => \RZP\Models\Card\IIN\Entity::class,
        'atom'          => \RZP\Gateway\Atom\Entity::class,
        'card'          => \RZP\Models\Card\Entity::class,
        'hdfc'          => \RZP\Gateway\Hdfc\Entity::class,
        'token'         => \RZP\Models\Customer\Token\Entity::class,
        'order'         => \RZP\Models\Order\Entity::class,
        'refund'        => \RZP\Models\Payment\Refund\Entity::class,
        'webhook'       => \RZP\Models\Merchant\Webhook\Entity::class,
        'methods'       => \RZP\Models\Merchant\Methods\Entity::class,
        'balance'       => \RZP\Models\Merchant\Balance\Entity::class,
        'payment'       => \RZP\Models\Payment\Entity::class,
        'pricing'       => \RZP\Models\Pricing\Entity::class,
        'customer'      => \RZP\Models\Customer\Entity::class,
        'merchant'      => \RZP\Models\Merchant\Entity::class,
        'terminal'      => \RZP\Models\Terminal\Entity::class,
        'emi_plan'      => \RZP\Models\Emi\Entity::class,
        'axis_migs'     => \RZP\Gateway\AxisMigs\Entity::class,
        'app_token'     => \RZP\Models\Customer\AppToken\Entity::class,
        'adjustment'    => \RZP\Models\Adjustment\Entity::class,
        'settlement'    => \RZP\Models\Settlement\Entity::class,
        'transaction'   => \RZP\Models\Transaction\Entity::class,
        'bank_account'  => \RZP\Models\BankAccount\Entity::class,
        'credits'       => \RZP\Models\Merchant\Credits\Entity::class,
        'address'       => \RZP\Models\Address\Entity::class,
        'wallet'        => \RZP\Gateway\Wallet\Base\Entity::class,
        'plan'          => \RZP\Models\Plan\Entity::class,
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
        $entity = snake_case(explode('\\', get_class($this))[5]);

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
        $entity = snake_case(explode('\\', get_class($this))[5]);

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
        return $this->db->transaction(function () use ($callable)
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
