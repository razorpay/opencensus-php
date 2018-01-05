<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const MERCHANT_TRAN_ID = 'merchanttranid';
    const SERVICE_TAX      = 'service_tax';
    const COMMISSION       = 'commission';
    const STATUS           = 'status';
    const AMOUNT           = 'amount';

    protected function getPaymentId(array $row)
    {
        return $row[self::MERCHANT_TRAN_ID] ?? null;
    }

    protected function getGatewayFee($row)
    {
        return $row[self::COMMISSION] ?? null;
    }

    protected function getGatewayServiceTax($row)
    {
        return $row[self::SERVICE_TAX] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        // If status is not set, assuming status to be failed
        $status = strtolower($row[self::STATUS]) ?? 'failed';

        if (strpos($status, 'suc') !== false)
        {
            return Status::AUTHORIZED;
        }

        return Status::FAILED;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return get_payment_amount($row[self::AMOUNT]);
    }
}