<?php

namespace RZP\Reconciliator\NetbankingDlb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingDlb\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Constants::PAYMENT_AMT;

    const PII_COLUMNS = [
        Constants::DEBIT_ACCOUNT_NO,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Constants::PAYMENT_ID];
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::HOST_REF_NO];
    }

    protected function getAccountDetails($row)
    {
        $debitAccNo = $row[Constants::DEBIT_ACCOUNT_NO];

        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $debitAccNo
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::PAYMENT_AMT]);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Constants::PAYMENT_DATE];
    }

    protected function getArn($row)
    {
        return $row[Constants::HOST_REF_NO];
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
