<?php

namespace RZP\Gateway\Netbanking\Equitas;

class ResponseFields
{
    const MERCHANT_ID               = 'PID';
    const PAYMENT_ID                = 'BRN';
    const AMOUNT                    = 'AMT';
    const ACCOUNT_NUMBER            = 'ACCNO';
    const MODE                      = 'MODE';
    const DESCRIPTION               = 'NAR';
    const BANK_PAYMENT_ID           = 'TID';
    const AUTH_STATUS               = 'STATUS';
    const ERROR_CODE                = 'errorCode';
    const ERROR_MESSAGE             = 'errorMessage';
    const CHECKSUM                  = 'checkval';

    const VERIFY_STATUS             = 'STATUS';
    const VERIFY_CHECKSUM_STATUS    = 'CHECKSUMSTATUS';
    const VERIFICATION              = 'VERIFICATION';
    const VERIFY_ERROR_CODE         = 'ERRCODE';
    const VERIFY_ERROR_MESSAGE      = 'ERRMSG';
}
