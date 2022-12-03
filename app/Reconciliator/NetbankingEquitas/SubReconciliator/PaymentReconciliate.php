<?php

namespace RZP\Reconciliator\NetbankingEquitas\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\NetbankingEquitas\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    const PII_COLUMNS = [
        Constants::ACCOUNT_NUMBER,
    ];

    public function getPaymentId(array $row)
    {
        return trim($row[Constants::GATEWAY_REFERENCE_NUMBER] ?? null);
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[Constants::BANK_TRANSACTION_ID] ?? null;
    }

    protected function getReconPaymentStatus(array $row): string
    {
        $status = $row[Constants::STATUS];

        if ($status === Constants::PAYMENT_STATUS_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    public function getGatewayPaymentDate($row)
    {
        return $row[Constants::DATE_OF_TRANSACTION] ?? null;
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

    public function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getAccountDetails($row): array
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Constants::ACCOUNT_NUMBER]
        ];
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[Constants::AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::AMOUNT]);
        }
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getReference1());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway Transaction ID (reference1) in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setReference1($gatewayTransactionId);
    }
}
