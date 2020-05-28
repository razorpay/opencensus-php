<?php

namespace RZP\Services\NbPlus;

class Response
{
    const RESPONSE = 'response';
    const ERROR    = 'error';
    const DATA     = 'data';

    // account
    const ACCOUNT_NUMBER = 'account_number';
    const BANK_IFSC      = 'bank_ifsc';

    // authorize
    const NEXT     = 'next';
    const REDIRECT = 'redirect';

    // callback & verify
    const GATEWAY_REFERENCE_NUMBER = 'gateway_reference_number';
    const STATUS                   = 'status';
    const ACCOUNT_INFO             = 'account_info';
    const PAYMENT_ID               = 'payment_id';

    // verify
    const GATEWAY_STATUS = 'gateway_status';
}
