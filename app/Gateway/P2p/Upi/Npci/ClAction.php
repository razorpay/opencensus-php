<?php

namespace RZP\Gateway\P2p\Upi\Npci;

class ClAction
{
    // Used to get challenge for registration and rotation
    const GET_CHALLENGE         = 'getChallenge';

    const REGISTER_APP          = 'registerApp';

    const GET_CREDENTIAL        = 'getCredential';

    // These are get credential errors

    const SET                   = 'set';
    const RESET                 = 'reset';
    const CHANGE                = 'change';
    const BALANCE               = 'balance';
    const DEBIT                 = 'debit';
    const RECURRING_DEBIT       = 'recurring_debit';
}
