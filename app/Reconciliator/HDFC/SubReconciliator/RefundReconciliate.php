<?php

namespace RZP\Reconciliator\HDFC\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\HDFC\Reconciliate;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID                      = 'merchant_trackid';
    const COLUMN_REFUND_AMOUNT                  = ['domestic_amt', 'intnl_amt'];
    const COLUMN_INTERNATIONAL_REFUND_AMOUNT    = 'paycur_usd';
    const COLUMN_ARN                            = 'arn_no';
    const COLUMN_GATEWAY_TRANSACTION_ID         = 'tran_id';
    const COLUMN_INR_REFUND_AMOUNT              = 'inr';

    const COLUMN_TERMINAL_NUMBER                = 'terminal_number';
    const COLUMN_CARD_TRIVIA                    = 'card_type';

    /**
     * If we are not able to find refund id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In HDFC MIS, many gst params and other params are always set to 0,
     * therefore if less than 10% of data is present, we don't mark row as failure.
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.10;

    protected $gatewayRefund;

    /**
     * In case refund id is not set, function will return null,
     * row will be marked as failure in such case.
     *
     * @param array $row
     *
     * @return null|string
     */
    protected function getRefundId($row)
    {
        if ($this->isCybersource($row) === true)
        {
            $refundId = $this->getRefundIdForCybersource($row);
        }
        else if ($this->isBharatQrIsg($row))
        {
            $refundId = $this->getRefundIdForIsg($row);
        }
        else
        {
            $refundId = $this->getRefundIdForFss($row);
        }

        if (empty($refundId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $refundId;
    }

    protected function getRefundIdForFss(array $row)
    {
        $refundId = null;

        if (empty($row[self::COLUMN_REFUND_ID]) === false)
        {
            $refundId = $row[self::COLUMN_REFUND_ID];

            $refundId = trim(str_replace("'", '', $refundId));
        }

        return $refundId;
    }

    protected function getRefundIdForCybersource(array $row)
    {
        //
        // Currently, the way to get refundId for a Cybersource
        // refund is the same as for FSS refund. Keeping two
        // different functions for clarity sake and easy reading.
        //
        return $this->getRefundIdForFss($row);
    }

    protected function getRefundIdForIsg(array $row)
    {
        $refundId = $this->getRefundIdForFss($row);

        // Isg file will contain refund id in format - 'razorrfnd{id}'
        $refundId = substr($refundId, 9);

        return $refundId;
    }

    /**
     * This function returns paymentId of refund using gateway refund entity.
     * Flow comes here when we try to create missing refund in db. Hence,
     * gateway refund is used here to get the payment id.
     *
     * @param array $row
     * @return null
     */
    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getReconRefundAmount(array $row)
    {
        $amountColumn = ($this->isInternationalRefund($row) === true) ?
                        self::COLUMN_INTERNATIONAL_REFUND_AMOUNT :
                        self::COLUMN_REFUND_AMOUNT;

        $refundAmountColumns = (is_array($amountColumn) === false) ?
                                [$amountColumn] :
                                $amountColumn;

        $refundAmountColumn = array_first(
            $refundAmountColumns,
            function ($amount) use ($row)
            {
                return (array_key_exists($amount, $row) === true);
            });

        if ($refundAmountColumn === null)
        {
            // None of the expected payment columns set
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_ABSENT,
                    'refund_id'         => $this->refund->getId(),
                    'expected_column'   => $amountColumn,
                    'amount'            => $this->refund->getBaseAmount(),
                    'currency'          => $this->payment->getCurrency(),
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        //
        // for HDFC, sometimes we are getting rows where 'domestic_amt' column is 0
        // and amount is given in 'intnl_amt' column. In this case, we can't just calculate
        // the recon amount on first key set. Instead we should calculate on the non zero
        // amount column if any.
        //
        $refundAmountNonZeroColumn = array_first(
            $refundAmountColumns,
            function ($amount) use ($row)
            {
                return (empty($row[$amount]) === false);
            });

        if ($refundAmountNonZeroColumn === null)
        {
            // There was no non zero amount column, so returning 0
            return 0;
        }

        return Helper::getIntegerFormattedAmount($row[$refundAmountNonZeroColumn]);
    }

    protected function getArn(array $row)
    {
        $arn = null;

        //
        // In case ARN in MIS is empty we don't fill RRN in place of ARN because
        // it can be empty because of issue in MIS file also, and later we can receive ARNs in updated MIS file.
        // Saving RRN earlier and uploading correct file later will throw errors of mismatches and ARNs will not be updated.
        //
        if (empty($row[self::COLUMN_ARN]) === false)
        {
            $arn = $row[self::COLUMN_ARN];

            $arn = trim(str_replace("'", '', $arn));

            if (stripos($arn, 'onus') !== false)
            {
                $arn = $this->getRRNForOnusTransaction($row);
            }
        }

        return $arn;
    }

    protected function getGatewayRefund(string $refundId)
    {
        if ((empty($this->gatewayRefund) === false) and
            ($this->gatewayRefund->getRefundId() === $refundId))
        {
            return $this->gatewayRefund;
        }

        $gatewayEntities = [];

        if ($this->refund->getGateway() === Gateway::HDFC)
        {
            $gatewayEntities = $this->repo->hdfc->findSuccessfulRefundByRefundId($refundId);
        }

        if ($this->refund->getGateway() === Gateway::CYBERSOURCE)
        {
            $gatewayEntities = $this->repo->cybersource->findSuccessfulRefundByRefundId($refundId);
        }

        if ($this->refund->getGateway() === Gateway::ISG)
        {
            $gatewayEntities = $this->repo->isg->findSuccessfulRefundByRefundId($refundId);
        }

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $refundEntity = $gatewayEntities->first();

        $this->gatewayRefund = $refundEntity;

        return $refundEntity;
    }

    protected function getGatewayTransactionId(array $row)
    {
        $gatewayTransactionId = null;

        if (empty($row[self::COLUMN_GATEWAY_TRANSACTION_ID]) === false)
        {
            $gatewayTransactionIdValue = str_replace("'", '', $row[self::COLUMN_GATEWAY_TRANSACTION_ID]);

            if (filled($gatewayTransactionIdValue) === true)
            {
                $gatewayTransactionId = trim($gatewayTransactionIdValue);
            }
        }

        return $gatewayTransactionId;
    }

    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        if ($gatewayRefund->getEntityName() === Entity::HDFC)
        {
            $gatewayRefund->setArnNo($arn);
        }
    }

    protected function isCybersource(array $row)
    {
        $terminalId = null;

        if (empty($row[self::COLUMN_TERMINAL_NUMBER]) === false)
        {
            $terminalId = $row[self::COLUMN_TERMINAL_NUMBER];

            $terminalId = trim(str_replace("'", '', $terminalId));
        }

        $isCybersource = Reconciliate::isCybersourceTerminalId($terminalId);

        return $isCybersource;
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
            return ((filled($value)) and ($value !== "' "));
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }

    /**
     * In case of onus transaction, we don't receive ARN.
     * Storing 12 digit RRN in place of ARN, to share as a transaction reference with customers
     * If that is also not set, gateway transaction id is used in place of rrn.
     * @param array $row
     * @return string
     */
    protected function getRRNForOnusTransaction(array $row): string
    {
        $refundRrn = $this->getRefundRrn($row);

        $rrn = (blank($refundRrn) === false) ?
                $refundRrn :
                $this->getGatewayTransactionId($row);

        if (blank($rrn) === true)
        {
            $rrn = 'NA';
        }

        return $rrn;
    }

    /**
     * Returns RRN from gateway refund entity
     *
     * @param array $row
     * @return mixed
     */
    protected function getRefundRrn(array $row)
    {
        $rrn = null;

        $refundId = $this->getRefundId($row);

        $gatewayRefund = $this->getGatewayRefund($refundId);

        if ($gatewayRefund !== null)
        {
            $rrn = $gatewayRefund->getRef();
        }

        return $rrn;
    }

    /**
     * In case on Non INR refunds, a non empty field of 'inr' is set in transaction row and
     * convert_currency will be false for such payment.
     * @param array $row
     * @return bool
     */
    protected function isInternationalRefund(array $row)
    {
        $inrAmountColumnSet =  (empty($row[self::COLUMN_INR_REFUND_AMOUNT]) === false) ? true : false;

        $convertCurrencyFlag = $this->payment->getConvertCurrency();

        if (($inrAmountColumnSet === true) and ($convertCurrencyFlag === false))
        {
            return true;
        }

        return false;
    }

    protected function isBharatQrIsg(array $row)
    {
        if ((isset($row[self::COLUMN_CARD_TRIVIA]) === true) and
            ($row[self::COLUMN_CARD_TRIVIA] === Reconciliate::BHARAT_QR_TYPE))
        {
            return true;
        }

        return false;
    }

    /*
     * This method needs to be handled specifically for Isg gateway, so needs to be overridden
     */
    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayRefund)
    {
        $dbGatewayTransactionId = (string) $gatewayRefund->getGatewayTransactionId();

        $gateway = $this->payment->getGateway();

        // for Isg gateway we do not receive bank reference number in the case of refunds. So we will persist
        // the bank_ref_no in the recon flow. So preventing alert from being raised for isg refunds
        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId) and
            ($gateway !== Entity::ISG))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setGatewayTransactionId($gatewayTransactionId);
    }
}
