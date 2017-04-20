<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'Refund ID';
    const COLUMN_PAYMENT_ID = 'Ref. 1';
    const COLUMN_REFUND_AMOUNT = 'Refund Amount (Rs. Ps.)';

    /**
     * BillDesk reconciliation files only send us the gateway refund ID,
     * which is mapped to api's refund id in BillDesk gateway db.
     *
     * @param array $row
     * @return string Refund ID
     */
    protected function getRefundId(array $row)
    {
        $gatewayRefundId = $row[self::COLUMN_REFUND_ID];

        if (empty($gatewayRefundId) === true)
        {
            return null;
        }

        $billDeskRepo = $this->app['repo']->billdesk;

        $refundId = $billDeskRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }
}
