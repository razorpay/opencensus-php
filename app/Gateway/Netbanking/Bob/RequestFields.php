<?php

namespace RZP\Gateway\Netbanking\Bob;

class RequestFields
{
    // Data that goes encrypted
    const MERCHANT_ID      = 'BankId';
    const BANK_FIXED_VALUE = 'PID';
    const BILLER_NAME      = 'PRN';
    const AMOUNT           = 'AMT';
    const CALLBACK_URL     = 'RU';
    const PAYMENT_ID       = 'ITC';

    const ENCRYPTED_DATA = 'encdata';
}
