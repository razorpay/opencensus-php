<?php

namespace RZP\Reconciliator\NetbankingIcici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Icici;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN';
    const COLUMN_BANK_PAYMENT_ID = 'BID';
    const COLUMN_PAYMENT_DATE    = 'Date';

    protected $netbankingRepo;

    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
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

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     [Icici\Confirmation::YES]);
    }

    /**
     * Allowing force authorize for all the failed payments.
     * Few reasons of failure are :
     * 1. Verify returns failure if multiple payments are created at bank's side,
     *    as verify return status of first failed payment.
     * 2. Payments happened at midnight duration, difference in dates at bank's system and Razorpay system.
     */
    protected function setAllowForceAuthorization()
    {
        $this->allowForceAuthorization = true;
    }
}
