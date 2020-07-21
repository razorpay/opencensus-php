<?php

namespace RZP\Reconciliator\BillDesk\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'refund_id';
    const COLUMN_PAYMENT_ID         = 'ref_1';
    const COLUMN_RZP_REFUND_ID      = 'ref_3';
    const COLUMN_REFUND_AMOUNT      = 'refund_amount_rs_ps';
    const COLUMN_BANK_REFERENCE_NO  = 'bank_ref_no';

    const BLACKLISTED_COLUMNS = [];

    /**
     * BillDesk reconciliation files only send us the gateway refund ID,
     * which is mapped to api's refund id in BillDesk gateway db.
     * EDIT : Now we are pre-processing the file contents, and for nb_plus
     * migrated refunds, calling scrooge to fetch the RZP refund ID and
     * setting it in ref_3 column.
     *
     * @param array $row
     * @return string Refund ID
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_RZP_REFUND_ID] ?? null;

        if (Entity::verifyUniqueId($refundId, false) === true)
        {
            return $refundId;
        }

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
                    'trace_code'            => TraceCode::RECON_MISMATCH,
                    'info_code'             => Base\InfoCode::REFUND_ABSENT,
                    'refund_reference_id'   => $gatewayRefundId,
                    'gateway'               => $this->gateway
                ]);

            $this->setFailUnprocessedRow(true);
        }

        return $refundId;
    }

    protected function getArn(array $row)
    {
        return $row[self::COLUMN_BANK_REFERENCE_NO] ?? null;
    }

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? null;
    }

    //
    // Just in case you decide to implement gateway_settled_at for refunds, don't.
    // The settled_at present in the refund MIS files is of the corresponding payment.
    //
}
