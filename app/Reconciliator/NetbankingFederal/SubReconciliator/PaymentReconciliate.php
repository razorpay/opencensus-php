<?php

namespace RZP\Reconciliator\NetbankingFederal\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Federal;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN';
    const COLUMN_BANK_PAYMENT_ID = 'BID';
    const COLUMN_PAYMENT_DATE    = 'Date';
    const COLUMN_PAYMENT_AMOUNT  = 'Amount';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            if (strpos($row[self::COLUMN_PAYMENT_REF_NO], '.') !== false)
            {
                return explode('.', $row[self::COLUMN_PAYMENT_REF_NO])[0];
            }

            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

//     protected function getReferenceNumber($row)
//     {
//         if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
//         {
//             return $row[self::COLUMN_BANK_PAYMENT_ID];
//         }
//
//         return null;
//     }

    public function getGatewayPayment($paymentId)
    {
        $status = [Federal\Status::getAuthSuccessStatus()];

        return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }
}
