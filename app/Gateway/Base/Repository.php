<?php

namespace RZP\Gateway\Base;

use RZP\Base;
use RZP\Base\ConnectionType;

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
        $query = $this->newQuery()
            ->where(Entity::PAYMENT_ID, '=', $paymentId)
            ->where('action', '=', $action)
            ->orderBy(Entity::CREATED_AT, 'desc');

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $query->firstOrFail();
        }

        $data = $query->first();

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->where(Entity::PAYMENT_ID, '=', $paymentId)
                ->where('action', '=', $action)
                ->orderBy(Entity::CREATED_AT, 'desc')
                ->firstOrFail();
        }

        return $data;
    }

    public function findByPaymentIdAndAction($paymentId, $action)
    {
        $data = $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $data;
        }

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->where(Entity::PAYMENT_ID, '=', $paymentId)
                ->where('action', '=', $action)
                ->orderBy(Entity::CREATED_AT, 'desc')
                ->first();
        }

        return $data;
    }

    public function findByPaymentIdAndActionGetLast($paymentId, $action)
    {
        return $this->findByPaymentIdAndAction($paymentId, $action);
    }

    public function fetchByPaymentIdsAndAction($paymentIds, $action)
    {
        $data = $this->newQuery()
                    ->whereIn('payment_id', $paymentIds)
                    ->where('action', '=', $action)
                    ->get();

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $data;
        }

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->whereIn('payment_id', $paymentIds)
                ->where('action', '=', $action)
                ->get();
        }

        return $data;
    }

    public function fetchByPaymentIdsAndActions($paymentIds, $actions)
    {
        $data = $this->newQuery()
                    ->whereIn('payment_id', $paymentIds)
                    ->whereIn('action', $actions)
                    ->get();

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $data;
        }

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->whereIn('payment_id', $paymentIds)
                ->whereIn('action', $actions)
                ->get();
        }

        return $data;
    }

    public function findByPaymentIdActionAndStatus(string $paymentId,
                                                   string $action,
                                                   array $statuses)
    {
        $data = $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->whereIn('status', $statuses)
                    ->first();

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $data;
        }

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->where(Entity::PAYMENT_ID, '=', $paymentId)
                ->where('action', '=', $action)
                ->whereIn('status', $statuses)
                ->first();
        }

        return $data;
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

    public function findByRefundIdAndAction(string $refundId, string $action)
    {
        return $this->newQuery()
                    ->where(Entity::REFUND_ID, '=', $refundId)
                    ->where(Entity::ACTION, '=', $action)
                    ->get();

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

    public function findByPaymentIdAndActionGetLastOrFail($paymentId, $action)
    {
        $query = $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('action', '=', $action)
                    ->orderBy(Entity::CREATED_AT, 'desc');

        if ($this->isExperimentEnabled(self::TIDB_GATEWAY_FALLBACK) === false)
        {
            return $query->firstOrFail();
        }

        $data = $query->first();

        if (empty($data) === true)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $data = $this->newQueryWithConnection($connectionType)
                ->where(Entity::PAYMENT_ID, '=', $paymentId)
                ->where('action', '=', $action)
                ->orderBy(Entity::CREATED_AT, 'desc')
                ->firstOrFail();
        }

        return $data;
    }
}
