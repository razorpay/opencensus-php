<?php

namespace RZP\Gateway\Netbanking\Equitas;

class RequestFields
{
    const MERCHANT_ID               = 'PID';
    const PAYMENT_ID                = 'BRN';
    const AMOUNT                    = 'AMT';
    const RETURN_URL                = 'RU';
    const ACCOUNT_NUMBER            = 'ACCNO';
    const MODE                      = 'MODE';
    const DESCRIPTION               = 'NAR';
    const CHECKSUM                  = 'checkval';

    const VERIFY_BANK_PAYMENT_ID    = 'TID';
}
