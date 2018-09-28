<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID = 'partner_txn_id';
    const COLUMN_AMOUNT     = 'original_input_amt';
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';

    protected $method;

    protected function getPaymentId(array $row)
    {
        $paymentId = null;

        if (isset($row[self::COLUMN_PAYMENT_ID]) === true)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];
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
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'actual_amount'   => $this->getReconPaymentAmount($row),
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
        if ($this->payment->getMethod() === Method::WALLET)
        {
            $gatewayPayment = $this->repo->wallet->fetchWalletByPaymentId($paymentId);
        }
        else
        {
            $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction($paymentId, 'authorize');
        }

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
        $func = 'BankPaymentId';

        if ($this->payment->getMethod() === Method::WALLET)
        {
            $func = 'GatewayPaymentId';
        }

        $getFunc = 'get'.$func;
        $setFunc = 'set'.$func;

        $dbGatewayTransactionId = trim($gatewayPayment->$getFunc());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayPaymentId))
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

        $gatewayPayment->$setFunc($gatewayPaymentId);
    }
}
