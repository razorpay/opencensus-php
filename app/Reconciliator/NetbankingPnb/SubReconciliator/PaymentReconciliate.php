<?php

namespace RZP\Reconciliator\NetbankingPnb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Pnb\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT = ReconFields::AMOUNT;

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[ReconFields::PAYMENT_ID]) === false)
        {
            return trim($row[ReconFields::PAYMENT_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[ReconFields::BANK_PAYMENT_ID]) === true)
        {
            $referenceNumber = $row[ReconFields::BANK_PAYMENT_ID];

            return $referenceNumber;
        }

        return null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }
}
