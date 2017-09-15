<?php

namespace RZP\Gateway\Netbanking\Corporation;

class Constants
{
    const MODE_OF_TRANSACTION_PAYMENT = 'P';

    const MODE_OF_TRANSACTION_VERIFY = 'V';

    const FUND_TRANSFER = 'T';

    const REFUND_FILE_DEBIT = 'D';

    const REFUND_FILE_CREDIT = 'C';

    const REFUND_FILE_ACCOUNT_TYPE_1 = 'CA   ';
    const REFUND_FILE_ACCOUNT_TYPE_2 = 'SB   ';

    const REFUND_FILE_ACCOUNT_SUB_TYPE = '01';
}
