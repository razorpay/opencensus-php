<?php

namespace RZP\Reconciliator\Mpesa\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID         = 'partner_txn_id';
    const COLUMN_COMMISSION         = 'basic_charge_rs';
    const COLUMN_SERVICE_TAX        = ['cgst', 'sgst', 'igst', 'utgst', 'cess'];
    const COLUMN_AMOUNT             = 'txn_amount_rs';
    const COLUMN_GATEWAY_PAYMENT_ID = 'm_pesa_txn_id';

    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (isset($row[self::COLUMN_PAYMENT_ID]) === true)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];
        }

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $gatewayServiceTax = 0;

        foreach (self::COLUMN_SERVICE_TAX as $serviceTax)
        {
            if (isset($row[$serviceTax]) === true)
            {
                $gatewayServiceTax += Helper::getIntegerFormattedAmount($row[$serviceTax]);
            }
        }

        return $gatewayServiceTax;
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = $this->getGatewayServiceTax($row);

        if (isset($row[self::COLUMN_COMMISSION]) === true)
        {
            $gatewayFee += Helper::getIntegerFormattedAmount($row[self::COLUMN_COMMISSION]);
        }

        return $gatewayFee;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->payment->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[self::COLUMN_AMOUNT]) === false)
        {
            return 0;
        }

        return Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
    }

    public function getGatewayPayment($paymentId)
    {
        $gatewayPayment = $this->repo->wallet->findByPaymentIdAndAction($paymentId, 'authorize');

        return $gatewayPayment;
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (empty($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === true)
        {
            return null;
        }

        return $row[self::COLUMN_GATEWAY_PAYMENT_ID];
    }

    protected function setGatewayTransactionId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayPaymentId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);
    }
}
