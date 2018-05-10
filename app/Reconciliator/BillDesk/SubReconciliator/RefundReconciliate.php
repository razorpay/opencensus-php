<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use Razorpay\Spine\Exception\DbQueryException;

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
            $this->setFailUnprocessedRow(false);

            return null;
        }

        $refundId = null;

        $billDeskRepo = $this->app['repo']->billdesk;

        try
        {
            $refundId = $billDeskRepo->findByGatewayRefundId($gatewayRefundId)->getRefundId();
        }
        catch (DbQueryException $ex)
        {
            /**
             * Flow comes here when gateway refund not found in DB.
             * It generally happens if refund failed because of gateway timeout
             * and DB doesn't have gateway refund id, sent in MIS file.
             */
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_MISMATCH,
                    'info_code'       => 'REFUND_ABSENT',
                    'message'         => 'Refund not found. Skipping.',
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            $this->setFailUnprocessedRow(true);
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    //
    // Just in case you decide to implement gateway_settled_at for refunds, don't.
    // The settled_at present in the refund MIS files is of the corresponding payment.
    //
}
