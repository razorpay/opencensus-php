<?php

namespace RZP\Reconciliator\NetbankingAusfCorp\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingAusfCorp\Constants;
use App;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Constants::PAYMENT_AMT;

    const PII_COLUMNS = [
        Constants::DEBIT_ACCOUNT_NO,
    ];

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[Constants::USERREFERENCENO] ?? null;
        if($paymentId !== null)
        {
            // Trimming the first char in file to clean up the data
            $paymentId = substr($paymentId, 1);
        }
        return $paymentId;
    }

    protected function getReferenceNumber($row)
    {
        $referenceNumber = $row[Constants::EXTERNALREFERENCEID_EXT] ?? null;
        if($referenceNumber !== null)
        {
            // Trimming the first char in file to clean up the data
            $referenceNumber = substr($referenceNumber, 1);
        }
        return $referenceNumber;
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
        return $this->getReferenceNumber($row);
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
