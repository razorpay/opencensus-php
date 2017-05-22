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
    public function getOrdersWithMultipleAuthorizedOrCapturedPayments($timestamp)
    {
        // SELECT count(*),
        //        orders.id
        // FROM `orders`
        // INNER JOIN `payments` ON `payments`.`order_id` = `orders`.`id`
        // WHERE `payments`.`status` IN (?,
        //                               ?)
        //   AND `payments`.`created_at` > ?
        //   AND `orders`.`created_at` > ?
        // GROUP BY `orders`.`id` HAVING count(*) > 1

        $paymentOrderId = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $paymentCreatedAt = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);
        $orderId = $this->dbColumn(Entity::ID);
        $orderCreatedAt = $this->dbColumn(Entity::CREATED_AT);
        $paymentStatusArray = [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED];
        $pTable = $this->repo->payment->getTableName();

        $results = $this->newQuery()
            ->join(
                $pTable,
                $paymentOrderId, '=', $orderId)
            ->selectRaw('count(*), ' . $orderId)
            ->whereIn($paymentStatus, $paymentStatusArray)
            ->where($paymentCreatedAt, '>', $timestamp)
            ->where($orderCreatedAt, '>', $timestamp)
            ->groupBy($orderId)
            ->havingRaw('count(*) > 1')
            ->with('payments')
            ->get();

        return $results;
    }
}
