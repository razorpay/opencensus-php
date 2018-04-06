<?php

namespace RZP\Reconciliator\NetbankingObc;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Obc\Status;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID      = 'PGI/Merchant Transaction Ref#';
    const BANK_PAYMENT_ID = 'Bank Transaction Ref#';
    const PAYMENT_DATE    = 'Transaction Date';
    const AMOUNT          = 'Transaction Amount';

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_PAYMENT_ID] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::PAYMENT_DATE] ?? null;
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
        return Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        [Status::SUCCESS]
                    );
    }
}
