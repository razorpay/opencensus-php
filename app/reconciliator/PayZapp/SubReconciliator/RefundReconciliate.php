<?php

namespace Reconciliator\PayZapp;

use Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'PG TXN ID';

    protected function getRefundId($row)
    {
        $gatewayRefundId = $row[self::COLUMN_REFUND_ID];

        $payzappRepo = $this->app['repo']->wallet_payzapp;

        $refundId = $payzappRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();

        return $refundId;
    }
}