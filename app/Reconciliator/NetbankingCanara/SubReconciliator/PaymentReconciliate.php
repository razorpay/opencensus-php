<?php

namespace RZP\Reconciliator\NetbankingCanara;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected $netbankingRepo;

    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        return $row[Constants::COLUMN_PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::COLUMN_BANK_PAYMENT_ID] ?? null;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId,
            Action::AUTHORIZE);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Constants::COLUMN_PAYMENT_DATE];
    }

    protected function getAccountDetails($row)
    {
        return [Base\Reconciliate::ACCOUNT_NUMBER => $row[Constants::CUSTOMER_ACCOUNT_NUMBER]];
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[Constants::COLUMN_PAYMENT_AMOUNT]);
    }
}


