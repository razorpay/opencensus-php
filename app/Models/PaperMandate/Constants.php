<?php

namespace RZP\Models\PaperMandate;

use RZP\Models\BankAccount\AccountType;

class Constants
{
    const PAPER_MANDATE_START_AFTER_DAYS = 4;
    const MAX_SIGNED_URL_TIMEOUT         = 10080;
    const SHORT_URL_BUFFER_TIME          = 1;
    const MAX_SIGNED_URL_TIMEOUT_IN_DAYS = 7;

    const SHARED_TERMINAL_MERCHANT_ID    = '100000Razorpay';
    const SHARED_TERMINAL_MERCHANT_NAME  = 'Razorpay Software Pvt Ltd';

    const NACH_EXTRA_BANK_ACCOUNT_TYPES = [
        AccountType::CC,
        AccountType::NRE,
        AccountType::NRO
    ];
}
