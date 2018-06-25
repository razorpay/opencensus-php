<?php

namespace RZP\Reconciliator\CardFss;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Card\Fss\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    const COLUMN_GATEWAY_TRANSACTION_ID   = 'payment_gateway_payment_transaction_id';
    const COLUMN_PAYMENT_ID               = 'merchant_track_id';
    const COLUMN_TRANSACTION_AMOUNT       = 'transaction_amount';
    const COLUMN_RRN                      = 'rrn';
    const COLUMN_AUTH_CODE                = 'authapproval_code';
    const COLUMN_GATEWAY_FEE              = 'msf';
    const COLUMN_GATEWAY_SERVICE_TAX      = 'msf_tax_amount';
    const COLUMN_GATEWAY_SETTLED_AT       = 'settlement_date';

    const SETTLEMENT_DATE_FORMAT     = 'd/m/Y';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

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

        return Base\Helper::getIntegerFormattedAmount($paymentAmount);
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
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
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
        if (empty($row[self::COLUMN_GATEWAY_SERVICE_TAX]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_GATEWAY_SERVICE_TAX);

            return null;
        }

        // Convert service tax into paise
        $serviceTax = Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_GATEWAY_SERVICE_TAX]);

        return abs($serviceTax);
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getGatewayFee($row)
    {
        if (empty($row[self::COLUMN_GATEWAY_FEE]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_GATEWAY_FEE);

            return null;
        }

        $fee =  Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_GATEWAY_FEE]);

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

    protected function getGatewayPayment($paymentId)
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
    }
}
