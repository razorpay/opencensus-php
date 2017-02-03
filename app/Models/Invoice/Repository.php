<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'invoice';

    protected $entityFetchParamRules = [
        Entity::PAYMENT_ID => 'sometimes|string|size:18',
        Entity::RECEIPT    => 'sometimes|string|min:1|max:40',
    ];

    protected $proxyFetchParamRules = [
        Entity::USER_ID => 'sometimes|alpha_num',
        Entity::STATUS  => 'sometimes|string',
        Entity::TYPE    => 'sometimes|string|max:16',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::ORDER_ID    => 'sometimes|string|max:20',
    ];

    public function fetchForOrder($order)
    {
        $invoice = $this->newQuery()
                        ->where(Entity::ORDER_ID, '=', $order->getId())
                        ->first();

        if ($invoice !== null)
        {
            $order->setRelation('invoice', $invoice);

            $invoice->order()->associate($order);
        }

        return $invoice;
    }

    public function getInvoicesForIssuedNotificationToCustomer($medium)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where($medium . '_status', '=', NotifyStatus::PENDING)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::SCHEDULED_AT, '<=', $currentTime)
                    ->get();
    }

    public function getInvoicesForExpiringNotificationToCustomer()
    {
        //
        // TODO:
        // - As there is no status attached with this mail, use logic to avoids
        // sending reminder twice.
        // - Start with fix value (how before to send the expiring mails), though this
        // needs to be tuned based on created_at and expire_by values.
        //

        return [];
    }

    public function getIssuedAndPastExpiredByInvoices()
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }

    public function getNonFailedPaymentsCount(Entity $invoice)
    {
        return $invoice->payments()
                       ->where(Payment\Entity::STATUS, '!=', Payment\Status::FAILED)
                       ->count();
    }

    protected function addQueryParamPaymentId($query, $params)
    {
        $this->joinQueryPayment($query);

        $paymentId = $params[Entity::PAYMENT_ID];
        Entity::stripSignWithoutValidation($paymentId);

        $paymentIdAttribute = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $query->where($paymentIdAttribute, '=', $paymentId);

        $query->select($query->getModel()->getTable() . '.*');
    }

    protected function addQueryParamOrderId($query, $params)
    {
        $orderId = (new Order\Entity)->verifyIdAndSilentlyStripSign($params[Entity::ORDER_ID]);

        $query->where(Entity::ORDER_ID, '=', $orderId);
    }

    protected function joinQueryPayment($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ?? [];

        foreach ($joins as $join)
        {
            if ($join->table === $this->manager->payment->getTableName())
            {
                return;
            }
        }

        $invoiceOrderId = $this->getAttributeWithTableName(Entity::ORDER_ID);
        $paymentOrderId = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ORDER_ID);

        $query->join($this->manager->payment->getTableName(), $invoiceOrderId, '=', $paymentOrderId);
    }
}
