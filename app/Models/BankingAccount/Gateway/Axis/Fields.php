<?php

namespace RZP\Models\BankingAccount\Gateway\Axis;

class Fields
{
    const CLIENT_ID      = 'clientId';
    const CORP_CODE      = 'corpCode';
    const CLIENT_SECRET  = 'clientSecret';
    const ENCRYPTION_IV  = 'encryptionIv';
    const ENCRYPTION_KEY = 'encryptionKey';

    const DATA                   = 'data';
    const REMARKS                = 'remarks';
    const BANK_STATUS_CODE       = 'bank_status_code';
    const ACCOUNT_BALANCE_AMOUNT = 'accountBalanceAmount';
}
