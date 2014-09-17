<?php

namespace Tests\Functional\Fixtures;

use Eloquent;
use Tests\TestDummy\Factory;
use Models;

class Fixtures
{
    protected static $entityMap = array(
        'balance'       => 'Models\Merchant\Balance',
        'key'           => 'Models\Key\Entity',
        'merchant'      => 'Models\Merchant\Entity',
        'pricing'       => 'Models\Pricing\Entity',
        'refund'        => 'Models\Transaction\Refund\Entity',
        'terminal'      => 'Models\Terminal\Entity',
        'transaction'   => 'Models\Transaction\Entity',
        'hdfc'          => 'Gateway\Hdfc\Entity',
    );

    public function createTransactionAuthorizedEntity(array $attributes = array())
    {
        $defaultValues = array('status' => 'authorized');

        $attributes = array_merge($attributes, $defaultValues);

        $txn = $this->createEntity('transaction', $attributes);

        $hdfcTxn = $this->createEntity(
            'hdfc',
            ['trackid' => $txn->getKey(), 'amount' => $txn->getAmount()]);

        return $txn;
    }

    public function createTransactionCapturedEntity(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'captured',
            'captured_at' => time(),
            'created_at' => time() - 10);

        $attributes = array_merge($attributes, $defaultValues);

        $txn = $this->createTransactionEntity($attributes);

        $hdfcTxn = $this->createEntity(
            'hdfc',
            ['trackid' => $txn->getKey(), 'amount' => $txn->getAmount()]);

        return $txn;
    }

    public function createTransactionEntity(array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id' => '363e4efa820b0c06208ccd99');

        $attributes = array_merge($attributes, $defaultValues);

        return $this->createEntity('transaction', $attributes);
    }

    protected function createHdfcTransactionAuthorizedEntity(array $attributes = array())
    {
        ;
    }

    /**
     * Seed the db with required data
     * This creates key entity and merchant entity
     * This key can be used by default for most use-cases
     * but you are not required to use it.
     */
    public function seedDbWithDefaultEntities()
    {
        $apiMerchant = $this->createEntity('merchant', ['id' => '134510ae166900007a9677a9']);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => '134510ae166900007a9677a9']);

        $this->entities = array(
            'pricing'     => $this->createDefaultPricingPlan(),
            'merchant'    => $this->createEntity('merchant', ['id' => '363e4efa820b0c06208ccd99']),
            'terminal'    => $this->createEntity('terminal', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'key'         => $this->createEntity('key', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'balance'     => $this->createEntity('balance', ['id' => '363e4efa820b0c06208ccd99']),
            'transaction' => $this->createEntity('transaction', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            );
    }

    public function createEntity($entity, $attributes = array())
    {
        if (($entity === 'merchant') or
            ($entity === 'pricing'))
        {
            return $this->createEntityInTestAndLive($entity, $attributes);
        }

        return $this->save($entity, $attributes);
    }

    protected function createEntityInTestAndLive($entity, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$entityMap[$entity];

        $entity = Factory::build($entity, $attributes);

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->save();
        $liveEntity->setConnection('live')->save();

        $entity->exists = true;
    }

    protected function save($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entity = self::$entityMap[$entity];

        $entity = Factory::create($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    public function createDefaultPricingPlan()
    {
        $pricingPlanId = '13906d42c88a41ee4e2d812e';

        $rows = array(
                    array(
                        'id' => '13906d42c88a41ee4e2d812e',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => null,
                        'payment_issuer' => null,
                        'percent_rate' => '2000',
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '13906de1816d11113ef4f86f',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => 'AMEX',
                        'payment_issuer' => null,
                        'percent_rate' => 3000,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '13906df591e73f02d8afc302',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => 'DICL',
                        'payment_issuer' => null,
                        'percent_rate' => 3000,
                        'fixed_rate' => 0,
                    ),
                );

        $repo = new Models\Pricing\Repository;

        foreach ($rows as $row)
        {
            $pricing = new Models\Pricing\Entity;
            $pricing->fill($row);
            $repo->saveOrFail($pricing);
        }

        $pricing = $repo->getPricingPlanByIdOrFailPublic($pricingPlanId);

        return $pricing;
    }

    protected function eloquentUnguard()
    {
        Eloquent::unguard();
    }

    protected function eloquentReguard()
    {
        Eloquent::reguard();
    }
}