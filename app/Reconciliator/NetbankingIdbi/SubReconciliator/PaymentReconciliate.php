<?php

namespace RZP\Reconciliator\NetbankingIdbi\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingIdbi\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = ReconFields::PAYMENT_AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::PAYMENT_AMOUNT]) ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconFields::PAYMENT_DATE] ?? null;
    }
}
