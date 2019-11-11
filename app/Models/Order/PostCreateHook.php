<?php

namespace RZP\Models\Order;

use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\SubscriptionRegistration\Core as TokenRegistrationCore;

class PostCreateHook extends Hook
{

    protected $hooks = [
        ExtraParams::TOKEN     => 'createTokenRegistration',
        ExtraParams::TRANSFERS => 'createTransfers'
    ];
    /**
     * @var Entity
     */
    protected $order;

    public function __construct(array $input, Entity $order)
    {
        parent::__construct($input);

        $this->order = $order;
    }

    public function createTokenRegistration(array $tokenParams)
    {
        $customerId = $this->orderInput[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicId($customerId);

        $tokenRegistrationInput = [
            Constants\Entity::SUBSCRIPTION_REGISTRATION => $tokenParams
        ];

        (new TokenRegistrationCore())->createAuthLinkForOrder($tokenRegistrationInput, $this->order, $customer);
    }

    public function createTransfers(array $input)
    {
        $order = $this->order;

        $this->app['trace']->info(
            TraceCode::ORDER_TRANSFER_REQUEST,
            ['order_id' => $order->getId(), 'data' => $input]);

        try
        {
            $transfers = $this->repo->transaction(function() use ($order, $input)
            {
                $transfers = (new Transfer\Core())->createForOrder($order, $input);

                return $transfers;
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
