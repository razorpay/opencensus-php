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

    /**
     * Gets all the orders which have more than 1 payment in authorized or captured state.
     *
     * @return Base\Collection
     */
    public function getOrdersWithMultipleAuthorizedOrCapturedPayments()
    {
        // select count(*), orders.id
        // from `orders` inner join `payments` on `payments`.`order_id` = `orders`.`id`
        // where `payments`.`status` in (?, ?)
        // group by `orders`.`id`
        // having count(*) > 1

        $paymentOrderId = $this->repo->payment->getAttributeWithTableName(Payment\Entity::ORDER_ID);
        $paymentStatus = $this->repo->payment->getAttributeWithTableName(Payment\Entity::STATUS);
        $orderId = $this->getAttributeWithTableName(Entity::ID);
        $paymentStatusArray = [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED];
        $pTable = $this->repo->payment->getTableName();

        $results = $this->newQuery()
            ->join(
                $pTable,
                $paymentOrderId, '=', $orderId)
            ->selectRaw('count(*), ' . $orderId)
            ->whereIn($paymentStatus, $paymentStatusArray)
            ->groupBy($orderId)
            ->havingRaw('count(*) > 1')
            ->with('payments')
            ->get();

        return $results;
    }
}
