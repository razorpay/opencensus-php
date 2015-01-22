<?php

namespace Tests\Functional\Fixtures\Entity;

use Config;
use Eloquent;
use Tests\TestDummy\Factory;

class Base
{
    protected static $map = array(
        'atom'          => 'Gateway\Atom\Entity',
        'adjustment'    => 'Models\Adjustment\Entity',
        'balance'       => 'Models\Merchant\Balance',
        'bank_account'  => 'Models\Merchant\BankAccount',
        'card'          => 'Models\Card\Entity',
        'hdfc'          => 'Gateway\Hdfc\Entity',
        'iin'           => 'Models\Card\Detail',
        'key'           => 'Models\Key\Entity',
        'merchant'      => 'Models\Merchant\Entity',
        'payment'       => 'Models\Payment\Entity',
        'pricing'       => 'Models\Pricing\Entity',
        'refund'        => 'Models\Payment\Refund\Entity',
        'settlement'    => 'Models\Settlement\Entity',
        'terminal'      => 'Models\Terminal\Entity',
        'transaction'   => 'Models\Transaction\Entity'
    );

    public static $fixtures;

    public function create(array $attributes = array())
    {
        $entity = lcfirst(explode('\\', get_class($this))[4]);

        return $this->createEntity($entity, $attributes);
    }

    public function createEntity($entity, array $attributes = array())
    {
        if (($entity === 'merchant') or
            ($entity === 'pricing'))
        {
            return $this->createEntityInTestAndLive($entity, $attributes);
        }

        return $this->save($entity, $attributes);
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

}