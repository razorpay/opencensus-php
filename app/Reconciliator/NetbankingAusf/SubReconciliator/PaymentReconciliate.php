<?php

namespace RZP\Reconciliator\NetbankingAusf\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingAusf\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Constants::PAYMENT_AMT;

    const PII_COLUMNS = [
        Constants::DEBIT_ACCOUNT_NO,
    ];

    protected function getPaymentId(array $row)
    {
        $paymentID = $row[Constants::USERREFERENCENO] ?? null;

        if($paymentID !== null)
        {
            // Trimming the first char in file to clean up the data
            $paymentID = substr($paymentID, 1);
        }
        return $paymentID;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::HOST_REF_NO] ?? null;
    }

    protected function getAccountDetails($row)
    {
        $debitAccNo = $row[Constants::DEBIT_ACCOUNT_NO] ?? null;

        if($debitAccNo !== null)
        {
            // Trimming the first char in file to clean up the data
            $debitAccNo = substr($debitAccNo, 1);
        }

        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $debitAccNo
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::PAYMENT_AMT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Constants::STATUS];

        if ($status === Constants::PAYMENT_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Constants::PAYMENT_DATE] ?? null;
    }

    protected function getArn($row)
    {
        return $row[Constants::HOST_REF_NO] ?? null;
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
