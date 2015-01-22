<?php

namespace Tests\Functional\Fixtures;

use Models\Merchant;
use Config;
use Eloquent;
use Tests\TestDummy\Factory;
use Models;

class Fixtures
{
    protected static $entityMap = array(
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

    protected static $customMap = array(
        'captured_payment',
        'card_captured_payment',
        'netbanking_captured_payment',
        'authorized_payment',
        'card_authorized_payment',
        'netbanking_authorized_payment',
        'atom_terminal');

    protected $links = [];

    public function __construct()
    {
        $this->base = new Entity\Base;
    }

    public function times($times)
    {
        Factory::times($times);

        return $this;
    }

    /**
     * Seed the db with required data
     */
    public function setUp()
    {
        $apiMerchant = $this->create('merchant', ['id' => Merchant\Account::NODAL_ACCOUNT]);
        $apiBalance = $this->base->createEntityInTestAndLive('balance', ['id' => Merchant\Account::NODAL_ACCOUNT]);

        $apiMerchant = $this->create('merchant', ['id' => '1cXSLlUU8V9sXl', 'pricing_plan_id' => '1hDYlICobzOCYt']);
        $apiBalance = $this->base->createEntityInTestAndLive('balance', ['id' => '1cXSLlUU8V9sXl']);

        $this->base->connection('test');

        $this->create('pricing:default_plan');

        $entities = array(
//            'pricing'   => $this->createDefaultPricingPlan(),
            'merchant'  => $this->create('merchant', ['id' => '10000000000000']),
            'terminal'  => $this->create('terminal', ['merchant_id' => '10000000000000']),
            'balance'   => $this->create('balance', ['id' => '10000000000000']),
            );

        $this->testKey = $this->on('test')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheTestAuthKey'], 'test');
        $this->liveKey = $this->on('live')->create('key', ['merchant_id' => '10000000000000', 'id' => 'TheLiveAuthKey'], 'live');

        $this->ba = $this->on('live')->create('bank_account', ['merchant_id' => '10000000000000']);

        $entities['payment'] = $this->on('test')->create(
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

        $payment = $this->create('payment', $attributes);

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
        if ((isset($attributes['method'])) and
            ($attributes['method'] === 'card'))
        {
            ;
        }

        return $this->createPaymentCardCapturedEntity($attributes);
    }

    public function createPaymentCardCapturedEntity(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => $this->entities['terminal']->getKey(),
            'transaction_id' => null,
            'captured_at' => time(),
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->base->build('payment', $attributes);

        $hdfcAttrArray = array(
            'trackid' => $payment->getKey(),
            'amount' => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $card = $this->create('card');

        $payment->card()->associate($card);

        $payment->save();

        $txn = (new Models\Transaction\Core)->createFromPayment($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        $hdfcPaymentAuthorized = $this->createHdfcPaymentAuthorizedEntity(
            $hdfcAttrArray);

        $hdfcPaymentCaptured = $this->createHdfcPaymentCapturedEntity(
            $hdfcAttrArray);

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
        return $this->create('hdfc', $attributes);
    }

    protected function createHdfcPaymentCapturedEntity(array $attributes = array())
    {
        $attributes['action'] = 5;
        $attributes['status'] = 'captured';

        return $this->create('hdfc', $attributes);
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
        $pair = explode(':', $resource);

        if (isset($pair[1]) === false)
        {
            $pair[1] = '';
        }

        $entity = $pair[0];
        $method = $pair[1];

        $obj = null;

        $method = 'create'.studly_case(ucfirst($method));

        $class = __NAMESPACE__.'\Entity\\'.ucfirst($entity);

        if (class_exists($class))
        {
            if (isset($this->links[$entity]) === false)
            {
                $this->links[$entity] = new $class;
            }

            $obj = $this->links[$entity];

            return $obj->$method($attributes);
        }

        $obj = $this->base;
        $method = 'createEntity';

        $data = $obj->$method($pair[0], $attributes);

        return $data;
    }
}