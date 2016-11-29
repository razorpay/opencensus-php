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
        Entity::PAYMENT_ID  => 'sometimes|string|size:18'
    ];

    protected $appFetchParamRules = [
        Entity::STATUS              => 'sometimes|string',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
    ];

    protected $proxyFetchParamRules = [
        Entity::USER_ID => 'sometimes|alpha_num',
        Entity::STATUS  => 'sometimes|string',
        Entity::TYPE    => 'sometimes|string|max:16',
    ];

    public function getInvoicesForNotification($medium)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where($medium . '_status', '=', NotifyStatus::PENDING)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::SCHEDULED_AT, '<=', $currentTime)
                    ->get();
    }

    public function getExpiredInvoices()
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::DUE_BY, '<', $currentTime)
                    ->get();
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
