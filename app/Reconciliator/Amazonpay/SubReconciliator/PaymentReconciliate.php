<?php

namespace RZP\Reconciliator\Amazonpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID         = 'sellerorderid';
    const COLUMN_FEE                = ['transactionpercentagefee', 'transactionfixedfee'];
    const COLUMN_AMOUNT             = 'transactionamount';
    const COLUMN_GATEWAY_PAYMENT_ID = 'amazonorderreferenceid';

    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (isset($row[self::COLUMN_PAYMENT_ID]) === true)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];
        }

        return $paymentId;
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = 0;

        foreach(self::COLUMN_FEE as $fee)
        {
            if (isset($row[$fee]) === true)
            {
                $gatewayFee += Helper::getIntegerFormattedAmount($row[$fee]);
            }
        }

        return abs($gatewayFee);
    }

    protected function getGatewayServiceTax($row)
    {
        return 0;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if (($this->payment->getBaseAmount() === $this->getReconPaymentAmount($row)) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'row'             => $row,
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        if (isset($row[self::COLUMN_AMOUNT]) === false)
        {
            return 0;
        }

        return Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
    }

    protected function getGatewayPayment($paymentId)
    {
        $gatewayPayment = $this->repo->wallet->fetchWalletByPaymentId($paymentId);

        return $gatewayPayment;
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (isset($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === false)
        {
            return null;
        }

        return $row[self::COLUMN_GATEWAY_PAYMENT_ID];
    }

    protected function setGatewayTransactionId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            (($dbGatewayTransactionId === $gatewayPaymentId) === false))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);
    }
}
