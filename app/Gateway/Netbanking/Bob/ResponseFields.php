<?php

namespace RZP\Gateway\Netbanking\Bob;

class ResponseFields
{
    // Data that goes encrypted
    const AMOUNT                  = 'AMT';
    const BILLER_NAME             = 'ITC';
    const STATUS                  = 'STATUS';
    const BANK_REF_NUMBER         = 'BID';
    const CUSTOMER_ACCOUNT_NUMBER = 'DebtAccountNo';
    const PAYMENT_ID              = 'PRN';

    const ENCRYPTED_DATA          = 'encdata';
}
