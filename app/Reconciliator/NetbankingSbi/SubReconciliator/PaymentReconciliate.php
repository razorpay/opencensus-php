<?php

namespace RZP\Reconciliator\NetbankingSbi\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Sbi\Status;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const MERCHANT_ID           = 'merchant_id';
    const GATEWAY_REFERENCE_NUM = 'gateway_reference_number';
    const BANK_REFERENCE_NUM    = 'bank_transaction_referenceno';
    const TRANSACTION_AMOUNT    = 'transaction_amount';
    const TRANSACTION_STATUS    = 'status';
    const TRANSACTION_DATE      = 'transaction_date';

    protected $netbankingRepo;

    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        return $row[self::GATEWAY_REFERENCE_NUM] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_REFERENCE_NUM] ?? null;
    }

    protected function getGatewayPaymentId($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId,
            Action::AUTHORIZE);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::TRANSACTION_DATE] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[self::TRANSACTION_STATUS] ?? Status::SUCCESS;

        return $this->getApiPaymentStatus($status);
    }

    protected function getMerchantId(array $row)
    {
        return $row[self::MERCHANT_ID] ?? null;
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
        return Helper::getIntegerFormattedAmount($row[self::TRANSACTION_AMOUNT]);
    }

    private function getApiPaymentStatus(string $status)
    {
        if ($status === Status::SUCCESS)
        {
            return PaymentStatus::CAPTURED;
        }

        return PaymentStatus::FAILED;
    }
}
