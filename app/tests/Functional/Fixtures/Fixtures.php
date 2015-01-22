<?php

namespace Tests\Functional\Fixtures;

use Models\Merchant;
use Config;
use Eloquent;
use Tests\TestDummy\Factory;
use Models;

class Fixtures
{
    protected $links = [];

    protected $times = 0;

    public function __construct()
    {
        $this->base = new Entity\Base;
        Entity\Base::$fixtures = $this;
    }

    public static function getInstance()
    {
        return new static;
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
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
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
            'terminal_id' => '1n25f6uN5S1Z5a',
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
        list($entity, $method) = $this->getEntityAndMethodFromCreate($resource);

        $obj = null;

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