<?php

namespace RZP\Gateway\Base;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|min:14|max:18');

    public function findByPaymentId($id)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $id)
                    ->get();
    }

    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->first();
    }

    public function findByPaymentIdAndAction($paymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->first();
    }

    public function fetchByPaymentIdsAndAction($paymentIds, $action)
    {
        return $this->newQuery()
                    ->whereIn('payment_id', $paymentIds)
                    ->where('action', '=', $action)
                    ->get();
    }

    public function findByPaymentIdActionAndStatus(string $paymentId,
                                                   string $action,
                                                   array $statuses)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->whereIn('status', $statuses)
                    ->firstOrFail();
    }

    public function findByTraceIdAndAction($paymentId, $action)
    {
        return $this->newQuery()
                    ->where('int_payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->first();
    }

    public function findRefunds($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', 'refund')
                    ->get();
    }

    public function findByRefundId($refundId)
    {
        return $this->newQuery()
                    ->where(Entity::REFUND_ID, '=', $refundId)
                    ->first();
    }

    protected function addQueryParamPaymentId($query, $params)
    {
        $paymentId = $params[Entity::PAYMENT_ID];
        $ix = strpos($paymentId, '_');

        if ($ix !== false)
        {
            $paymentId = substr($paymentId, $ix + 1);
        }

        $query->where(Entity::PAYMENT_ID, '=', $paymentId);
    }

    public function retrieveByPaymentIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->firstOrFail();
    }
}
