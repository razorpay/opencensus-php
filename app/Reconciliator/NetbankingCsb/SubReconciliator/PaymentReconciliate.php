<?php

namespace RZP\Reconciliator\NetbankingCsb;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Models\Payment\Status as PaymentStatus;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID      = 'payment_id';
    const STATUS          = 'status';
    const AMOUNT          = 'amount';
    const DATE            = 'date';

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[self::STATUS] ?? Status::SUCCESS;

        return $this->getApiPaymentStatus($status);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::DATE] ?? null;
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
                    'gateway'         => 'netbanking_csb'
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    private function getApiPaymentStatus(string $status)
    {
        if ($status === Status::FAILURE)
        {
            return PaymentStatus::FAILED;
        }

        return PaymentStatus::CAPTURED;
    }
}
