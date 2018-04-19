<?php

namespace RZP\Reconciliator\NetbankingCsb;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

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

    const DELIMITER       = '^';

    protected function getTypeName($fileName)
    {
        // We only do a payment reconciliation process for CSB
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        assert($type === self::PAYMENT);

        //
        // We are returning an array directly instead of adding this array as a
        // property of the class. This is because adding this array as a property
        // of the class would leave additional memory allocated to the class
        // as long as there exists an object of this class within the current scope.
        // This would cause additional memory leaks to the PHP process.
        //

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
        return self::DELIMITER;
    }

    /**
     * Since the heades have a separator of " ^ " and the other rows have separator
     * "^",  i.e without spaces, we can ignore the headers row and map the rows
     * manually by setting the column headers above.
     *
     * @param array $fileDetails
     * @return array
     */
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
