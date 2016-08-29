<?php

namespace RZP\Gateway\Ebs;

class RequestConstants
{
    const ACCOUNT_ID            = 'account_id';
    const REFERENCE_NO          = 'reference_no';
    const AMOUNT                = 'amount';
    const CALLBACK              = 'return_url';
    const NAME                  = 'name';
    const ADDRESS               = 'address';
    const CITY                  = 'city';
    const COUNTRY               = 'country';
    const POSTAL_CODE           = 'postal_code';
    const PHONE                 = 'phone';
    const EMAIL                 = 'email';
    const DESCRIPTION           = 'description';
    const CURRENCY              = 'currency';
    const MODE                  = 'mode';
    const PAYMENT_MODE          = 'payment_mode';
    const CHANNEL               = 'channel';
    const NAME_ON_CARD          = 'name_on_card';
    const CARD_NUMBER           = 'card_number';
    const CARD_EXPIRY           = 'card_expiry';
    const CARD_NETWORK          = 'card_branch';
    const CARD_CVV              = 'card_cvv';
    const BANK_CODE             = 'bank_code';
    const PAYMENT_OPTION        = 'payment_option';
    const SECURE_HASH           = 'secure_hash';
    const ACTION                = 'Action';
    const SECRET_KEY            = 'secret_key';
    const PAYMENT_ID            = 'payment_id';

    //
    // These are for EBS API Request
    //
    const API_ACTION            = 'Action';
    const API_ACCOUNT_ID        = 'AccountID';
    const API_SECRET_KEY        = 'SecretKey';
    const API_AMOUNT            = 'Amount';
    const API_PAYMENT_ID        = 'PaymentID';
    const API_TRANSACTION_ID    = 'TransactionID';
    const API_REFERENCE_NO      = 'RefNo';
}
