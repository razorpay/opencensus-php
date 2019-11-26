<?php

namespace RZP\Reconciliator\CardFssHdfc\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Card\Fss\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    const COLUMN_GATEWAY_TRANSACTION_ID   = 'payment_gateway_transaction_id';
    const COLUMN_PAYMENT_ID               = 'merchant_track_id';
    const COLUMN_TRANSACTION_AMOUNT       = 'transaction_amount';
    const COLUMN_RRN                      = 'rrn';
    const COLUMN_AUTH_CODE                = 'authapproval_code';
    const COLUMN_GATEWAY_FEE              = 'msf_amount';
    const COLUMN_GATEWAY_SERVICE_TAX      = ['msf_tax_amount', 'gst_on_msf'];
    const COLUMN_GATEWAY_SETTLED_AT       = 'settlement_date';
    const COLUMN_CARD_HOLDER_NAME         = 'card_holder_name';

    const SETTLEMENT_DATE_FORMAT     = 'd/m/Y';

    /**
     * If we are not able to find payment id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In CardFSS MIS, last row can have some string like "END OF REPORT" or some random value`
     * So if less than 20% data is present in a row, we don't mark row unprocessing status as failure.
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.20;

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        if (empty($paymentId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = $row[self::COLUMN_TRANSACTION_AMOUNT];

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($paymentAmount);
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getAuthCode($row)
    {
        return $row[self::COLUMN_AUTH_CODE] ?? null;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = null;

        //
        // In new MIS files, we are getting GST with
        // column name GST/Service Tax
        //
        $serviceTaxColumn = array_first(self::COLUMN_GATEWAY_SERVICE_TAX, function ($cst) use ($row)
        {
            //
            // We are not using isset() here because as isset() returns false even when key is
            // set but the value is null.
            // e.g : $arr = ['a' => null]; isset(arr['a']) returns FALSE.
            // Here the column  we are checking can have value NULL or 0 or anything. We just want to
            // ensure that the column is present in the file, value can be null or anything. we should
            // return false only if the column itself is not present. so using array_key_exists().
            //
            return (array_key_exists($cst, $row) === true);
        });

        if ($serviceTaxColumn === null)
        {
            $this->reportMissingColumn($row, self::COLUMN_GATEWAY_SERVICE_TAX[0]);

            return null;
        }

        // Convert service tax into paise
        $serviceTax = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[$serviceTaxColumn]);

        return abs($serviceTax);
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getGatewayFee($row)
    {
        //
        // Can't put empty check, because msf can be zero
        // Checking if the column is present in the row. Not using isset() here, as isset()
        // returns FALSE even if the key is present but the value is NULL. We should return false
        // only if the column itself is not present
        //
        if (array_key_exists(self::COLUMN_GATEWAY_FEE, $row) === false)
        {
            $this->reportMissingColumn($row, self::COLUMN_GATEWAY_FEE);

            return null;
        }

        $fee =  Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_GATEWAY_FEE]);

        $fee = abs($fee);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return $fee;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_GATEWAY_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_GATEWAY_SETTLED_AT]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(self::SETTLEMENT_DATE_FORMAT, $columnSettledAt, Timezone::IST);
            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get Gateway Settled at',
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);
        }

        return $gatewaySettledAt;
    }

    public function getGatewayPayment($paymentId)
    {
        $status = Status::$successStates;

        return $this->repo
                    ->card_fss
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        $status
                    );
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            BaseReconciliate::REFERENCE_NUMBER => $this->getReferenceNumber($row)
        ];
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setRef($referenceNumber);
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_TRANSACTION_ID] ?? null;
    }

    protected function setPaymentAcquirerData($rowDetails)
    {
        //
        // In MIS file, we are not receiving ARN hence storing RRN in reference1 field of payment entity.
        // This is done because for reporting purposes, we need reference number in payment entity.
        //
        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === false)
        {
            $this->setPaymentReference1($rowDetails[BaseReconciliate::REFERENCE_NUMBER]);
        }

        if (empty($rowDetails[BaseReconciliate::AUTH_CODE]) === false)
        {
            $this->setPaymentReference2($rowDetails[BaseReconciliate::AUTH_CODE]);
        }
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
