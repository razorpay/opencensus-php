<?php

namespace RZP\Reconciliator\UpiHdfc;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const ORDER_ID              = 'order_id';

    const TXN_REFERENCE_NUMBER  = 'txn_ref_no_rrn';

    const SETTLEMENT_DATE       = 'settlement_date';

    const CURRENCY              = 'currency';

    const COLUMN_PAYMENT_AMOUNT = 'transaction_amount';

    /**
     * If we are not able to find payment id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In UPI HDFC MPR, last 10  rows has 1 column as  summary data and rest  is empty.
     * Therefore, if less than .05 of data is present, we don't mark row as failure
     */
    const MIN_ROW_FILLED_DATA_RATIO = .05;

    protected function getPaymentId(array $row)
    {
        $paymentId =  $row[self::ORDER_ID];

       if (empty($paymentId) === true)
       {
            $this->evaluateRowProcessedStatus($row);
       }

       return $paymentId;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::TXN_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $settledAt = $row[self::SETTLEMENT_DATE] ?? null;

        if (empty($settledAt) === true)
        {
            return null;
        }

        return Carbon::createFromFormat('d-M-Y  H:i:s', $settledAt, Timezone::IST)->timestamp;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getNpciReferenceId());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT] ?? null);
    }

    /**
     * This function evaluate and marks the row processing as success or failure based on
     * percentage of data available in a row.
     *
     * @param $row
     */
    private function evaluateRowProcessedStatus(array $row)
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
