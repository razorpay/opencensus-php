<?php

namespace Tests\Functional\Fixtures;

use Eloquent;
use Tests\TestDummy\Factory;
use Models;

class Fixtures
{
    protected static $entityMap = array(
        'balance'       => 'Models\Merchant\Balance',
        'card'          => 'Models\Card\Entity',
        'hdfc'          => 'Gateway\Hdfc\Entity',
        'key'           => 'Models\Key\Entity',
        'merchant'      => 'Models\Merchant\Entity',
        'payment'       => 'Models\Payment\Entity',
        'pricing'       => 'Models\Pricing\Entity',
        'refund'        => 'Models\Payment\Refund\Entity',
        'terminal'      => 'Models\Terminal\Entity',
        'transaction'   => 'Models\Transaction\Entity'
    );

    public function times($times)
    {
        Factory::times($times);

        return $this;
    }

    /**
     * Seed the db with required data
     * This creates key entity and merchant entity
     * This key can be used by default for most use-cases
     * but you are not required to use it.
     */
    public function setUp()
    {
        $apiMerchant = $this->createEntity('merchant', ['id' => '1cXSLlUU8V9sXl']);
        $apiBalance = $this->createEntityInTestAndLive('balance', ['id' => '1cXSLlUU8V9sXl']);

        $this->connection('test');

        $entities = array(
            'pricing'   => $this->createDefaultPricingPlan(),
            'merchant'  => $this->createEntity('merchant', ['id' => '10000000000000']),
            'terminal'  => $this->createEntity('terminal', ['merchant_id' => '10000000000000']),
            'balance'   => $this->createEntity('balance', ['id' => '10000000000000']),
            );

        $this->testKey = $this->createEntity('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->liveKey = $this->createEntity('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');

        $entities['payment'] = $this->createEntity(
                                        'payment',
                                        ['merchant_id' => '10000000000000',
                                         'terminal_id' => $entities['terminal']->getKey()]);

        $this->entities = $entities;
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

        $payment = $this->build('payment', $attributes);

        $hdfcAttrArray = array(
            'trackid' => $payment->getKey(),
            'amount' => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $hdfcPaymentAuthorized = $this->createHdfcPaymentAuthorizedEntity(
            $hdfcAttrArray);

        $hdfcPaymentCaptured = $this->createHdfcPaymentCapturedEntity(
            $hdfcAttrArray);

        $card = $this->createEntity('card');

        $payment->card()->associate($card);

        $txn = (new Models\Transaction\Core)->createFromPayment($payment);

        $payment->save();
        $txn->save();

        return $payment;
    }

    protected function createTransactionForPayment($payment)
    {
        ;
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

    public function createTerminalEntityForAtomGateway(array $attributes = array())
    {
        $attributes = array(
            'merchant_id' => '10000000000000',
            'gateway' => 'atom',
            'gateway_merchant_id' => 'abcd',
            'gateway_terminal_id' => 'abcde',
            'gateway_terminal_password' => 'abcdef');

        return $this->createEntity('terminal', $attributes);
    }

    public function createEntity($entity, $attributes = array(), $mode = 'test')
    {
        if (($entity === 'merchant') or
            ($entity === 'pricing'))
        {
            return $this->createEntityInTestAndLive($entity, $attributes);
        }

        return $this->save($entity, $attributes, $mode);
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

    protected function save($entity, $attributes, $mode = 'test')
    {
        $this->connection($mode);

        $this->eloquentUnguard();

        $entityClass = self::$entityMap[$entity];

        $entity = Factory::create($entityClass, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function build($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entity = self::$entityMap[$entity];

        $entity = Factory::build($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    public function createCard()
    {
        ;
    }

    public function generateUniqueId()
    {
        return \Models\Base\UniqueIdEntity::generateUniqueId();
    }

    public function createDefaultPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $rows = array(
                    array(
                        'id' => '1nvp2XPMmaRLxb',
                        'plan_id' => '1hDYlICobzOCYt',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => null,
                        'payment_issuer' => null,
                        'percent_rate' => '2000',
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '1OwH8rTI0ejFxS',
                        'plan_id' => '1hDYlICobzOCYt',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => 'AMEX',
                        'payment_issuer' => null,
                        'percent_rate' => 3000,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '1fq0OXpgeyafQq',
                        'plan_id' => '1hDYlICobzOCYt',
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

    protected function connection($mode = 'test')
    {
        \Config::set('database.default', $mode);

        return $this;
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