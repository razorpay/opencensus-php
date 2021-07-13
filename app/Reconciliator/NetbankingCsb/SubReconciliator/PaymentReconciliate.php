<?php

namespace RZP\Reconciliator\NetbankingCsb\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Models\Payment\Status as PaymentStatus;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const PAYMENT_ID      = 'payment_id';
    const BANK_REF_NO     = 'bank_payment_id';
    const STATUS          = 'status';
    const AMOUNT          = 'amount';
    const DATE            = 'date';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    public function getGatewayPayment($paymentId)
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
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => 'netbanking_csb'
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    private function getApiPaymentStatus(string $status)
    {
        if ($status === Status::FAILURE)
        {
            return PaymentStatus::FAILED;
        }

        return PaymentStatus::CAPTURED;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_REF_NO];
    }

    protected function getArn($row)
    {
        return $row[self::BANK_REF_NO] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
