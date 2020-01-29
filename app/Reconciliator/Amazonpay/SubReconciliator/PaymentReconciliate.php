<?php

namespace RZP\Reconciliator\Amazonpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID         = 'merchantorderid';
    const COLUMN_FEE                = ['ordercommission'];
    const COLUMN_AMOUNT             = 'orderamount';
    const COLUMN_GATEWAY_PAYMENT_ID = 'orderid';
    const COLUMN_GST                = 'gst';

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

        $serviceTax = $this->getGatewayServiceTax($row);

        $gatewayFee += $serviceTax;

        return abs($gatewayFee);
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = 0;

        if (isset($row[self::COLUMN_GST]) === false)
        {
            $this->reportMissingColumn($row, self::COLUMN_GST);

            return $serviceTax;
        }

        $gst = $row[self::COLUMN_GST];

        $serviceTax += Helper::getIntegerFormattedAmount($gst);

        return $serviceTax;
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
                    'gateway'         => $this->gateway,
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
