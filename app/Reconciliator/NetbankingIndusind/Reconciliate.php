<?php

namespace RZP\Reconciliator\NetbankingIndusind;

use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Indusind\ReconciliationFields;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_COLUMN_HEADER = [
        ReconciliationFields::PAYEE_ID,
        ReconciliationFields::AMOUNT,
        ReconciliationFields::ACCOUNT_NUMBER,
        ReconciliationFields::PAYMENT_ID,
        ReconciliationFields::DATE,
    ];

    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER
    ];

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::TYPE_TO_COLUMN_HEADER_MAP[$type];
    }

    public function getDelimiter()
    {
        return '^';
    }
}
