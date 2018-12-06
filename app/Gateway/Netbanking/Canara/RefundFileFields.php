<?php

namespace RZP\Gateway\Netbanking\Canara;

class RefundFileFields
{
    const TRANSACTION_DATE_TIME       = 'TRANSACTION DATE AND TIME';
    const REFUND_DATE                 = 'Refund Date';
    const BANK_REF_NO                 = 'BANK_REF_NO';
    const PG_REF_NUM                  = 'PG_REF_NUM';
    const REFUND_REFERENCE            = 'Refund Reference';
    const TRANSACTION_AMOUNT          = 'Transaction Amount';
    const REFUND_AMOUNT               = 'Refund Amount';

    const COLUMN_HEADERS              = [
                                          self::TRANSACTION_DATE_TIME,
                                          self::REFUND_DATE,
                                          self::BANK_REF_NO,
                                          self::PG_REF_NUM,
                                          self::REFUND_REFERENCE,
                                          self::TRANSACTION_AMOUNT,
                                          self::REFUND_AMOUNT
                                         ];


}
