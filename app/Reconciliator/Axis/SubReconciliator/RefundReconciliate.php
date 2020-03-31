<?php

namespace RZP\Reconciliator\Axis\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Cybersource;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID     = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_RRN            = 'rrn_no';
    const COLUMN_ARN            = 'arn';
    const COLUMN_ORDER_ID       = 'order_id';
    const COLUMN_MSG_TYPE       = 'msg_type';
    const COLUMN_MID            = 'mid';

    const PREAUTH               = 'PREAUTH';
    const CYBS                  = 'CYBS';

    const COLUMN_REFUND_AMOUNT  = 'txn_amount';

    /**
     * If we are not able to find refund id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In Old Axis MIS format, last row has around 9 out of 34 columns as stats data and
     * rest empty. (9/34)*100 = 26.47 %
     *
     * In new Axis MIS format, last row has around 10 out of 35 columns as stats data and
     * rest empty. (10/35)*100 = 28.57 %
     *
     * Therefore, if less than 29% of data is present, we don't mark row as failure
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.29;

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        //
        // In recent Axis files, we are getting refund IDs in the
        // column 'merchant_trans_ref', for MIGS and cybersource both.
        //
        $refundId = $this->getColumnPaymentId($row);

        if (UniqueIdEntity::verifyUniqueId($refundId, false) === true)
        {
            return $refundId;
        }

        if ($this->isCybersource($row) === true)
        {
            $refundId = $this->getRefundIdForCybersource($row);
        }
        else
        {
            $refundId = $this->getRefundIdForMigs($row);
        }

        if (empty($refundId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $refundId;
    }

    /**
     * Axis reconciliation files only send us the rrn which is mapped
     * to api's refund id in axis migs gateway db.
     *
     * @param array $row
     * @return string|null Refund ID
     */
    protected function getRefundIdForMigs(array $row)
    {
        $rrn = $row[self::COLUMN_RRN];

        if (empty($rrn) === true)
        {
            return null;
        }

        $refundId = null;

        try
        {
            $refundId = $this->repo->axis_migs->findByRrn($rrn)->getRefundId();
        }
        catch (DbQueryException $ex)
        {
            //
            // Finding refund id based on RRN, if RRN is missing
            // in DB, catches the exception and raises alert.
            //
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => Base\InfoCode::REFUND_ABSENT,
                    'refund_reference_id'       => $rrn,
                    'gateway'                   => $this->gateway
                ]);
        }

        return $refundId;
    }

    protected function getRefundIdForCybersource(array $row)
    {
        $refundId = null;

        $orderId = $row[self::COLUMN_ORDER_ID];

        $gatewayRefund = $this->repo->cybersource->findSuccessfulTxnByActionAndRef(
                                                            Cybersource\Action::REFUND, $orderId);

        if ($gatewayRefund !== null)
        {
            $refundId = $gatewayRefund->getRefundId();
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::REFUND_ABSENT,
                    'refund_reference_id'   => $orderId,
                    'gateway'               => $this->gateway
                ]);
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        if ($this->isCybersource($row) === true)
        {
            $paymentId = $this->getPaymentIdForCybersource($row);
        }
        else
        {
            $paymentId = $this->getPaymentIdForMigs($row);
        }

        return $paymentId;
    }

    protected function getPaymentIdForMigs(array $row)
    {
        $paymentId = $this->getColumnPaymentId($row);

        //
        // For Cybersource payments via Axis, we don't get a payment ID
        // in the self::COLUMN_PAYMENT_ID. We get some reference number.
        // This usually means that it's a Cybersource payment and we have
        // to get payment_id in a different way.
        //
        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return null;
        }

        return $paymentId;
    }

    protected function getPaymentIdForCybersource(array $row)
    {
        $paymentId = null;

        $merchantRef = $this->getColumnPaymentId($row);

        //
        // In the refund MIS file, all the merchant_trans_refs correspond to
        // the authorize row in the Cybersource entity, as opposed to the
        // captured row for order_ids in payment MIS file.
        //
        $gatewayPayment = $this->repo->cybersource->findsSuccessfulTxnByActionAndRef(
                                                        Cybersource\Action::AUTHORIZE, $merchantRef);

        if ($gatewayPayment !== null)
        {
            $paymentId = $gatewayPayment->getPaymentId();
        }

        return $paymentId;
    }

    /**
     * We get payment/refund ID under same column,
     * as it is combined file.
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getColumnPaymentId(array $row)
    {
        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                return $row[$cpi];
            }
        }

        return null;
    }

    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        $arn = $row[self::COLUMN_ARN];

        return $arn;
    }

    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setArn($arn);
    }

    protected function isCybersource(array $row)
    {
        $msgType = $mid = null;

        if (isset($row[self::COLUMN_MSG_TYPE]) === true)
        {
            $msgType = $row[self::COLUMN_MSG_TYPE];
        }

        if (isset($row[self::COLUMN_MID]) === true)
        {
            $mid = $row[self::COLUMN_MID];
        }

        if ((stripos($msgType, self::PREAUTH) === true) or
            (ends_with($mid, self::CYBS) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * This function evaluate and marks the row processing as success or failure based on
     * percentage of data available in a row.
     *
     * @param $row
     */
    protected function evaluateRowProcessedStatus(array $row)
    {
        $nonEmptyData = array_filter($row, function($value) {
            return filled($value);
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }
}
