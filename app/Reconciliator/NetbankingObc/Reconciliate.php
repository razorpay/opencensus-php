<?php

namespace RZP\Reconciliator\NetbankingObc;

use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Obc\ReconciliationFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
         ReconciliationFields::BANK,
         ReconciliationFields::GATEWAY_TRANSACTION_DATE,
         ReconciliationFields::PAYEE_ID,
         ReconciliationFields::TRANSACTION_AMOUNT,
         ReconciliationFields::MERCHANT_REFERENCE_NUMBER,
         ReconciliationFields::BANK_REFERENCE_NUMBER,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
