<?php

namespace RZP\Gateway\Netbanking\Bob;

class RequestFields
{
    // Data that goes encrypted
    const BANK_ID          = 'BankId';
    const BANK_FIXED_VALUE = 'PID';
    const BILLER_NAME      = 'ITC';
    const AMOUNT           = 'AMT';
    const CALLBACK_URL     = 'RU';
    const PAYMENT_ID       = 'PRN';
    const CUSTOMER_TYPE    = 'CRT';

    const ENCRYPTED_DATA   = 'encdata';
}
