<?php

namespace RZP\Models\Order;

use RZP\Constants;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Merchant\Methods;
use RZP\Models\Customer as Customer;
use RZP\Models\UpiMandate\Core as UpiMandateCore;
use RZP\Models\SubscriptionRegistration\Core as TokenRegistrationCore;

class PostCreateHook extends Hook
{

    protected $hooks = [
        ExtraParams::TOKEN     => 'createRegistrationEntity',
        ExtraParams::TRANSFERS => 'createTransfers'
    ];
    /**
     * @var Entity
     */
    protected $order;

    protected $publicKey;

    public function __construct(array $input, Entity $order)
    {
        parent::__construct($input);

        $this->order = $order;

        $this->publicKey = $input[Entity::PUBLIC_KEY] ?? null;
    }

    public function createRegistrationEntity($tokenParams)
    {
        if (($tokenParams === null) or
            ($tokenParams === []))
        {
            return;
        }

        $customerId = $this->orderInput[Entity::CUSTOMER_ID];

        $customerId = Customer\Entity::verifyIdAndSilentlyStripSign($customerId);

        $customer = $this->repo->customer->findById($customerId);

        if ((isset($this->orderInput[Entity::METHOD]) === true) and
            ($this->orderInput[Entity::METHOD]) === Methods\Entity::UPI)
        {
            $this->createUpiMandateEntity($tokenParams, $customer);
        }
        else
        {
            $this->createSubscriptionRegistrationEntity($tokenParams, $customer);
        }
    }

    protected function createUpiMandateEntity($tokenParams, $customer)
    {
        (new UpiMandateCore())->create($tokenParams, $this->order, $customer);
    }

    protected function createSubscriptionRegistrationEntity($tokenParams, $customer)
    {
        $tokenRegistrationInput = [
            Constants\Entity::SUBSCRIPTION_REGISTRATION => $tokenParams
        ];

        (new TokenRegistrationCore())->createAuthLinkForOrder($tokenRegistrationInput, $this->order, $customer);
    }

    public function createTransfers($input)
    {
        if (($input === null) or ($input === []))
        {
            return;
        }

        $order = $this->order;

        $input[Entity::PUBLIC_KEY] = $this->publicKey;

        $this->app['trace']->info(
            TraceCode::ORDER_TRANSFER_REQUEST,
            ['order_id' => $order->getId(), 'data' => $input]);

        try
        {
            $transfers = $this->repo->transaction(function() use ($order, $input)
            {
                return Tracer::inSpan(['name' => 'order.transfer.create'], function() use ($order, $input)
                {
                    return (new Transfer\Core())->createForOrder($order, $input);
                });
            });

            (new Transfer\Metric())->pushCreateSuccessMetrics($input);

            $this->app['trace']->info(
                TraceCode::ORDER_TRANSFER_SUCCESS,
                ['order_id' => $order->getId(), 'transfers' => $transfers]);

            $order->transfers = $transfers;
        }
        catch (\Exception $e)
        {
            (new Transfer\Metric())->pushCreateFailedMetrics($e);

            throw $e;
        }
    }
}
