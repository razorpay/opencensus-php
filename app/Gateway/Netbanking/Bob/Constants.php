<?php

namespace RZP\Gateway\Netbanking\Bob;

class Constants
{
    const BANK_FIXED_VALUE = '000000000745';

    const STATUS_SUCCESS = 'S';
    const STATUS_FAILURE = 'F';

    const VERIFY_PAIR_SEPARATOR = '|';
    const VERIFY_KEY_VALUE_SEPARATOR = '=';

    const REFUND_DEBIT  = 'D';
    const REFUND_CREDIT = 'C';

    const REFUND_PARTICULARS_HEAD = 'Razorpay Refund';
}
