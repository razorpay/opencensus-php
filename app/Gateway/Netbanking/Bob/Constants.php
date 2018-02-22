<?php

namespace RZP\Gateway\Netbanking\Bob;

class Constants
{
    const BANK_ID = '012';

    const BILLER_NAME = 'Razorpay';

    const CUSTOMER_TYPE_RETAIL = 'retail';
    const CUSTOMER_TYPE_CORPORATE = 'corporate';

    const VERIFY_PAIR_SEPARATOR = '|';
    const VERIFY_KEY_VALUE_SEPARATOR = '=';

    const REFUND_DEBIT  = 'D';
    const REFUND_CREDIT = 'C';

    const REFUND_PARTICULARS_HEAD = 'Razorpay Refund';
}
