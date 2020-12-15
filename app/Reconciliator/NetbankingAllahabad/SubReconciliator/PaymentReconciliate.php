<?php

namespace RZP\Reconciliator\NetbankingAllahabad\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\NetbankingAllahabad\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[Constants::PGI_REFERENCE_NO] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_REFERENCE_NO] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
            Action::AUTHORIZE);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $bankPaymentId = $this->getReferenceNumber($row);

        if (empty($bankPaymentId) === true)
        {
            return Status::FAILED;
        }
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
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[Constants::TRNX_AMOUNT]);
    }
}
