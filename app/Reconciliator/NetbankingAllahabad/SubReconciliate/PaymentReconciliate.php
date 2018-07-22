<?php

namespace RZP\Reconciliator\NetbankingAllahabad;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;

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
        return $row[Constants::PGI_REFERENCE_NO] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_REFERENCE_NO] ?? null;
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId,
            Action::AUTHORIZE);
    }

    /**
     * MIS contains failed payments also. If error code is non zero
     * and bank reference number is 0, status of payment is considered failed
     * otherwise success.
     *
     * @param array $row
     * @return null|string
     */
    protected function getReconPaymentStatus(array $row)
    {   

        $bankPaymentId = $this->getReferenceNumber($row);

        if ((empty($errorCode) === false) and (empty($bankPaymentId) === true))
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
        return Base\Helper::getIntegerFormattedAmount($row[Constants::TRNX_AMOUNT]);
    }
}
