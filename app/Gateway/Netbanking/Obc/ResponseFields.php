<?php

namespace RZP\Gateway\Netbanking\Obc;

class ResponseFields
{
    const PAID             = 'PAID';

    const BANK_PAYMENT_ID  = 'BID';

    const CURRENCY         = 'CRN';

    const AMOUNT           = 'AMT';

    const PAYEE_ID         = 'PID';

    const PAY_REF_NUM      = 'PRN';

    const ITEM_CODE        = 'ITC';

    const DEBIT_ACC_NUM    = 'DBACID';

    /**
     * Status of verify Txn
     */
    const TXN_STATUS       = 'TXN_STATUS';
}
