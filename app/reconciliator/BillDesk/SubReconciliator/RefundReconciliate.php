<?php

namespace Reconciliator\BillDesk;

use Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'Refund ID';

    /**
     * BillDesk reconciliation files only send us the gateway refund ID,
     * which is mapped to api's refund id in BillDesk gateway db.
     *
     * @param array $row
     * @return string Refund ID
     */
    protected function getRefundId($row)
    {
        $gatewayRefundId = $row[self::COLUMN_REFUND_ID];

        $billDeskRepo = $this->app['repo']->billdesk;

        $refundId = $billDeskRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();

        return $refundId;
    }
}