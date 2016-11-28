<?php

namespace RZP\Gateway\Netbanking\Icici;

class RequestFields
{
    const MODE_OF_OPERATION         = 'MD'; // P
    const PAYEE_ID                  = 'PID';
    const PAYMENT_REFERENCE_NUBER   = 'PRN';
    const ITEM_CODE                 = 'ITC';
    const AMOUNT                    = 'AMT';
    const CURRENCY_CODE             = 'CRN';
    const RETURN_URL                = 'RU';
    const ONLINE_CONFIRMATION       = 'CG'; // Y
    const ENCRYPTED_STRING          = 'ES'; // Encode all the data above apart from MD and PID
}
