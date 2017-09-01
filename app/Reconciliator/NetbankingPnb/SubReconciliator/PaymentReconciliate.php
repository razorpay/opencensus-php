<?php

namespace RZP\Reconciliator\NetbankingPnb;

use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Pnb\Status;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID          = 'payment_id';
    const COLUMN_BANK_PAYMENT_ID     = 'bank_reference';
    const COLUMN_BANK_ACCOUNT_NUMBER = 'account_number';
    const COLUMN_AMOUNT              = 'amount';
    const COLUMN_DATE                = 'date';

    protected function getPaymentId($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return ltrim($row[self::COLUMN_PAYMENT_ID]);
        }

        return null;
    }

    protected function getGatewayPayment($paymentId)
    {
        $status = [Status::SUCCESS];

        return $this->repo->netbanking
                          ->findByPaymentIdActionAndStatus($paymentId,
                                                           Action::AUTHORIZE,
                                                           $status);
    }

    protected function getNbAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $this->getDebitAccountNumber($row)
        ];
    }

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[self::COLUMN_BANK_ACCOUNT_NUMBER]) === false)
        {
            return ltrim($row[self::COLUMN_BANK_ACCOUNT_NUMBER]);
        }

        return null;
    }

    protected function shouldAttemptForceAuthorizeFailed()
    {
        return true;
    }
}
