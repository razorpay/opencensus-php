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
        'refund'        => 'Models\Payment\Refund\Entity',
        'terminal'      => 'Models\Terminal\Entity',
        'payment'       => 'Models\Payment\Entity',
        'hdfc'          => 'Gateway\Hdfc\Entity',
    );

    public function times($times)
    {
        Factory::times($times);

        return $this;
    }

    public function createPaymentAuthorizedEntity(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => $this->entities['terminal']->getKey(),
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createEntity('payment', $attributes);

        $hdfcPayment = $this->createHdfcPaymentAuthorizedEntity(
            array(
                'trackid' => $payment->getKey(),
                'amount' => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ));

        return $payment;
    }

    public function createPaymentCapturedEntity(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'captured',
            'terminal_id' => $this->entities['terminal']->getKey(),
            'captured_at' => time(),
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createEntity('payment', $attributes);

        $hdfcAttrArray = array(
            'trackid' => $payment->getKey(),
            'amount' => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $hdfcPaymentAuthorized = $this->createHdfcPaymentAuthorizedEntity(
            $hdfcAttrArray);

        $hdfcPaymentCaptured = $this->createHdfcPaymentCapturedEntity(
            $hdfcAttrArray);

        return $payment;
    }

    protected function createHdfcPaymentAuthorizedEntity(array $attributes = array())
    {
        $attributes['action'] = 4;
        $attributes['status'] = 'authorized';
        return $this->createEntity('hdfc', $attributes);
    }

    protected function createHdfcPaymentCapturedEntity(array $attributes = array())
    {
        $attributes['action'] = 5;
        $attributes['status'] = 'captured';

        return $this->createEntity('hdfc', $attributes);
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

        $entities = array(
            'pricing'   => $this->createDefaultPricingPlan(),
            'merchant'  => $this->createEntity('merchant', ['id' => '363e4efa820b0c06208ccd99']),
            'terminal'  => $this->createEntity('terminal', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'key'       => $this->createEntity('key', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'balance'   => $this->createEntity('balance', ['id' => '363e4efa820b0c06208ccd99']),
            );

        $entities['payment'] = $this->createEntity(
                                        'payment',
                                        ['merchant_id' => '363e4efa820b0c06208ccd99',
                                        'terminal_id' => $entities['terminal']->getKey()]);

        $this->entities = $entities;
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
        $entity->setRawAttributes($liveEntity->getAttributes(), true);

        return $entity;
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