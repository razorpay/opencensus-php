<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'order';

    protected $entityFetchParamRules = [
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::RECEIPT         => 'sometimes|string|max:40',
    ];

    protected $proxyFetchParamRules = [
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::NOTES           => 'sometimes|notes_fetch',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::ACCOUNT_NUMBER  => 'sometimes|string|max:50|min:5',
    ];

    public function fetchForPayment($payment)
    {
        if ($payment->hasRelation('order'))
        {
            return $payment->order;
        }

        $orderId = $payment->getApiOrderId();

        $order = $this->findOrFail($orderId);

        $payment->order()->associate($order);

        return $order;
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap = [])
    {
        $orders = $this->newQuery()
                       ->merchantId($merchantId)
                       ->betweenTime($from, $to)
                       ->with('payments')
                       ->take($count)
                       ->skip($skip)
                       ->latest()
                       ->get();

        return $orders;
    }

    /**
     * Checks for uniqueness across the orders of a particular merchant. Uniqueness across all the orders is not
     * checked.
     *
     * @param string $merchantId
     * @param string $receipt
     *
     * @return bool
     */
    public function isReceiptUnique(string $merchantId, string $receipt): bool
    {
        $ordersCount = $this->newQuery()
                            ->where(Entity::MERCHANT_ID, $merchantId)
                            ->where(Entity::RECEIPT, $receipt)
                            ->count();

        return ($ordersCount === 0);
    }
}
