<?php

namespace RZP\Models\Order;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'order';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
    );

    protected $entityFetchParamRules = array(
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::RECEIPT         => 'sometimes|string|max:40',
    );

    public function getOrderForPayment($payment)
    {
        $orderId = $payment->getApiOrderId();

        $order = $this->find($orderId);

        if ($order !== null)
        {
            $payment->order()->associate($order);
        }

        return $order;
    }
}
