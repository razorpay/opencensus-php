<?php

namespace Gateway\Base;

use Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|min:14|max:18');

    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->firstOrFail();
    }

    public function findByPaymentIdAndAction($paymentId, $action)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->first();
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
                    ->where('payment_id', '=', $paymentId)
                    ->where('action', '=', 'refund')
                    ->get();
    }

    protected function addQueryParamPaymentId($query, $params)
    {
        $paymentId = $params[Entity::PAYMENT_ID];
        $ix = strpos($paymentId, '_');

        if ($ix !== false)
        {
            $paymentId = substr($paymentId, $ix+1);
        }

        $query->where(Entity::PAYMENT_ID, '=', $paymentId);
    }
}