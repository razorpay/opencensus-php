<?php

namespace RZP\Gateway\Netbanking\Bob;

class Constants
{
    const BANK_FIXED_VALUE = '000000000745';

    const BILLER_NAME = 'Razorpay';

    const VERIFY_PAIR_SEPARATOR = '|';
    const VERIFY_KEY_VALUE_SEPARATOR = '=';

    const REFUND_DEBIT  = 'D';
    const REFUND_CREDIT = 'C';

    const REFUND_PARTICULARS_HEAD = 'Razorpay Refund';
}
