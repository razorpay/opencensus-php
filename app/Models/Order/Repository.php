<?php

namespace RZP\Models\Order;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Payment;

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

    public function getOrdersWithMultipleAuthorizedOrCapturedPayments()
    {
        // select count(*), orders.id
        // from `orders` inner join `payments` on `payments`.`order_id` = `orders`.`id`
        // where `payments`.`status` in (?, ?)
        // group by `orders`.`id`
        // having count(*) > 1

        $paymentOrderId = Payment\Entity::getAttributeWithTableName(Payment\Entity::ORDER_ID);
        $paymentStatus = Payment\Entity::getAttributeWithTableName(Payment\Entity::STATUS);
        $orderId = Entity::getAttributeWithTableName(Entity::ID);
        $paymentStatusArray = [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED];

        $results = $this->newQuery()
            ->join(
                Table::PAYMENT,
                $paymentOrderId, '=', $orderId)
            ->select($this->db->raw('count(*), ' . $orderId))
            ->whereIn($paymentStatus, $paymentStatusArray)
            ->groupBy($orderId)
            ->havingRaw('count(*) > 1')
            ->with('payments')
            ->get();

        return $results;
    }
}
