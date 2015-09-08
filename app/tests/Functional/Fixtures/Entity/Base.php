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
        'atom'          => Gateway\Atom\Entity::class,
        'adjustment'    => Models\Adjustment\Entity::class,
        'balance'       => Models\Merchant\Balance::class,
        'bank_account'  => Models\Merchant\BankAccount\Entity::class,
        'methods'       => Models\Merchant\Methods\Entity::class,
        'card'          => Models\Card\Entity::class,
        'hdfc'          => Gateway\Hdfc\Entity::class,
        'card_detail'   => Models\Card\Detail::class,
        'key'           => Models\Key\Entity::class,
        'merchant'      => Models\Merchant\Entity::class,
        'methods'       => Models\Merchant\Methods\Entity::class,
        'payment'       => Models\Payment\Entity::class,
        'pricing'       => Models\Pricing\Entity::class,
        'refund'        => Models\Payment\Refund\Entity::class,
        'settlement'    => Models\Settlement\Entity::class,
        'terminal'      => Models\Terminal\Entity::class,
        'transaction'   => Models\Transaction\Entit::class,
    );

    public function create(array $attributes = array())
    {
        $entity = snake_case(explode('\\', get_class($this))[4]);

        return $this->createEntity($entity, $attributes);
    }

    public function createEntity($entity, array $attributes = array())
    {
        if (($entity === 'merchant') or
            ($entity === 'pricing') or
            ($entity === 'methods'))
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
        if (($entity === 'merchant') or
            ($entity === 'pricing') or
            ($entity === 'methods'))
        {
            return $this->editEntityInTestAndLive($entity, $id, $attributes);
        }

        $entity = $entity::findOrFail($id);

        foreach ($attributes as $key => $value)
        {
            $entity[$attribute] = $value;
        }

        $entity->saveOrFail($merchant);

        return $entity;
    }

    public function createEntityInTestAndLive($entity, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$map[$entity];

        $entity = Factory::build($entity, $attributes);

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->save();
        $liveEntity->setConnection('live')->save();

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

        $testEntity->setConnection('test')->save();
        $liveEntity->setConnection('live')->save();

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
