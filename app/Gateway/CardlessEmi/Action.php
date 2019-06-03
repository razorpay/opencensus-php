<?php

namespace RZP\Gateway\CardlessEmi;

class Action
{
    const AUTHORIZE      = 'authorize';
    const CAPTURE        = 'capture';
    const VERIFY         = 'verify';
    const REFUND         = 'refund';
    const REVERSE        = 'reverse';
    const CHECK_ACCOUNT  = 'check_account';
    const FETCH_TOKEN    = 'fetch_token';
    const VERIFY_REFUND  = 'verify_refund';
}
