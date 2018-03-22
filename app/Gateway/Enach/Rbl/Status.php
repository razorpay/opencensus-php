<?php

namespace RZP\Gateway\Enach\Rbl;

class Status
{
    const DEBIT_SUCCESS = '';
    const DEBIT_REJECT  = 'REJECT';

    const ACKNOWLEDGE_SUCCESS = 'true';
    const ACKNOWLEDGE_FAILURE = 'false';

    const REGISTRATION_SUCCESS = 'active';
    const REGISTRATION_FAILURE = '';
}