<?php

namespace RZP\Reconciliator\NetbankingUjjivan\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingUjjivan\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Constants::PAYMENT_AMT;

    const PII_COLUMNS = [
        Constants::ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Constants::PAYMENT_ID];
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_PAYMENT_ID];
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Constants::ACCOUNT_NUMBER] ?? null
        ];
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Constants::PAYMENT_TXNDATE] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::PAYMENT_AMT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Constants::PAYMENT_STATUS];

        if ($status === Constants::TRANSACTION_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
