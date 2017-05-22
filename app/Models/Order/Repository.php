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
     * Gets all the paid orders which have any authorized payments.
     *
     * @return Base\Collection
     */
    public function getPaidOrdersWithAuthorizedPayments()
    {
        // Raw SQL:
        // SELECT orders.id
        // FROM orders
        // INNER JOIN payments ON payments.order_id = orders.id
        // WHERE orders.status = 'paid' AND payments.status = 'authorized'

        $orderId     = $this->dbColumn(Entity::ID);
        $orderStatus = $this->dbColumn(Entity::STATUS);

        $paymentTable   = $this->repo->payment->getTableName();
        $paymentStatus  = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $paymentOrderId = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);

        $results = $this->newQuery()
                        ->join($paymentTable, $paymentOrderId, '=', $orderId)
                        ->selectRaw($orderId)
                        ->where($orderStatus, Status::PAID)
                        ->where($paymentStatus, Payment\Status::AUTHORIZED)
                        ->with('payments')
                        ->get();

        return $results;
    }
}
