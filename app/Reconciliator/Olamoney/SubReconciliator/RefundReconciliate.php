<?php

namespace RZP\Reconciliator\Olamoney;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'Global Merchant Id';

    protected function getRefundId($row)
    {
        $gatewayRefundId = $row[self::COLUMN_REFUND_ID];

        $olamoneyRepo = $this->app['repo']->wallet_olamoney;

        $refundId = $olamoneyRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();

        return $refundId;
    }
}