<?php

namespace RZP\Reconciliator\PayZapp;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'PG TXN ID';

    protected function getRefundId($row)
    {
        $gatewayRefundId = $row[self::COLUMN_REFUND_ID];

        if (empty($gatewayRefundId) === true)
        {
            return null;
        }

        $payzappRepo = $this->app['repo']->wallet_payzapp;

        $refundId = $payzappRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();

        return $refundId;
    }

    protected function createRefundOnApi(array $row, string $refundId, \Exception $ex)
    {
        return false;
    }
}
