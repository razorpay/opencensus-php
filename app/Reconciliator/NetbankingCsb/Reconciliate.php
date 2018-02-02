<?php

namespace RZP\Reconciliator\NetbankingCsb;

use RZP\Reconciliator\Base;

/**
 * This class was developed as per the sample file shared by the CSB POC.
 * @see https://drive.google.com/drive/folders/15d5rWx9w8CctZJTPvipE3tWGrm0AEpRE
 *
 * Class Reconciliate
 * @package RZP\Reconciliator\NetbankingCsb
 */
class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_ID      = 'payment_id';
    const BANK_PAYMENT_ID = 'bank_payment_id';
    const PAYEE_ID        = 'payee_id';
    const AMOUNT          = 'amount';
    const STATUS          = 'status';
    const DATE            = 'date';

    protected function getTypeName($fileName)
    {
        // We only do a payment reconciliation process for CSB
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        assert($type === self::PAYMENT);

        return [
            self::PAYMENT_ID,
            self::BANK_PAYMENT_ID,
            self::PAYEE_ID,
            self::AMOUNT ,
            self::STATUS,
            self::DATE
        ];
    }

    public function getDelimiter()
    {
        return '^';
    }
}
