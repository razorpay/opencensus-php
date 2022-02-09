<?php

namespace RZP\Reconciliator\NetbankingHdfcCorp\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingHdfcCorp\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[Constants::COLUMN_PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_PAYMENT_ID] ?? null;
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
        $statusCode = $row[Constants::STATUS_CODE] ?? 0;

        $bankPaymentId = $this->getReferenceNumber($row);

        $errorMsg = $row[Constants::ERROR_DESCRIPTION];

        if (($statusCode !== "102") or (empty($bankPaymentId) === true) or (empty($errorMsg) === false))
        {
            return Status::FAILED;
        }

        return Status::AUTHORIZED;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::COLUMN_PAYMENT_AMOUNT]);
    }
}
