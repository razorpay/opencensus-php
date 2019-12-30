<?php

namespace RZP\Services\NbPlus;

class Response
{
    const RESPONSE = 'response';
    const ERROR    = 'error';

    // account
    const ACCOUNT_NUMBER = 'account_number';
    const BANK_IFSC      = 'bank_ifsc';

    // authorize
    const NEXT = 'next';

    // callback & verify
    const GATEWAY_REFERENCE_NUMBER = 'gateway_reference_number';
    const STATUS                   = 'status';
    const ACCOUNT_INFO             = 'account_info';
}
