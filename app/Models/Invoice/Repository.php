<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Plan\Subscription;

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
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
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

    /**
     * Once an invoice is issued for a subscription, it MUST
     * be charged, irrespective of whether the invoice has been expired
     * or the subscription has been cancelled.
     *
     * @return Base\PublicCollection
     */
    public function getSubscriptionInvoicesToCharge()
    {
        // TODO: Should we still charge the invoice if
        // the subscription has been cancelled? Can we
        // charge and then refund the amount if the subscription
        // was not cancelled by the time the invoice was created?

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->whereNotNull(Entity::SUBSCRIPTION_ID)
                    ->with('subscription')
                    ->get();
    }

    public function fetchIssuedInvoicesOfSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->where(Entity::STATUS, '=', Status::ISSUED)
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
