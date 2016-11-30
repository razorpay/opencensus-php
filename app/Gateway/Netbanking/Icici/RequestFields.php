<?php

namespace RZP\Gateway\Netbanking\Icici;

class RequestFields
{
    const MODE                      = 'MD';
    const PAYEE_ID                  = 'PID';
    const PAYMENT_REFERENCE_NUBER   = 'PRN';
    const ITEM_CODE                 = 'ITC';
    const AMOUNT                    = 'AMT';
    const CURRENCY_CODE             = 'CRN';
    const RETURN_URL                = 'RU';
    const CONFIRMATION              = 'CG';
    const ENCRYPTED_STRING          = 'ES'; // Encode all the data above apart from MD and PID
}
