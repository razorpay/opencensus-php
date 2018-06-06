<?php

namespace RZP\Reconciliator\Atom;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID          = 'merchant_txn_id';
    const COLUMN_AMOUNT              = 'gross_txn_amount';
    const COLUMN_BANK_REFERENCE_NO   = 'bank_ref_no';
    const COLUMN_ATOM_TRANSACTION_ID = 'atom_txn_id';
    const COLUMN_TRANSACTION_CHARGES = 'txn_charges';
    const COLUMN_SERVICE_TAX         = 'gst_18';
    const COLUMN_SETTLED_AT          = 'settlement_date';

    const SETTLEMENT_DATE_FORMAT     = 'd-M-Y h:i:s';

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->atom->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function getReconPaymentAmount($row)
    {
        if (isset($row[self::COLUMN_AMOUNT]) === true)
        {
            return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
        }

        return null;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_SERVICE_TAX]);
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_REFERENCE_NO] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_ATOM_TRANSACTION_ID] ?? null;
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
                    'info_code'       => 'AMOUNT_MISMATCH',
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayFee($row)
    {
        $fee = Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_TRANSACTION_CHARGES]);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

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

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        // Overriding this because atom entity has gatewaypaymentid not gatewaytransactionid
        $dbGatewayTransactionId = $gatewayPayment->getGatewayPaymentId();

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayTransactionId);
    }
}
