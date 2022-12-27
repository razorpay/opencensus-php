<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

class Fields
{
    const SDK                       = 'sdk';
    const CALLBACK                  = 'callback';
    const CONTENT                   = 'content';
    const TYPE                      = 'type';

    // --------------------------- Device --------------- //
    const CONTACT                   = 'c';
    const TOKEN                     = 't';
    const GATEWAY_TOKEN             = 'gateway_token';

    // --------------------------- Mandate --------------- //
    const PAYER_VPA                 = 'pr';
    const PAYEE_VPA                 = 'pa';
    const AMOUNT                    = 'am';
    const AMOUNT_RULE               = 'amrule';
    const TRANSACTION_NOTE          = 'tn';
    const TRANSACTION_REFERENCE     = 'tr';
    const MCC                       = 'mc';
    const URL                       = 'url';
    const RECUR_TYPE                = 'recurtype';
    const RECUR                     = 'recur';
    const RECUR_VALUE               = 'recurvalue';
    const VALIDITY_START            = 'validitystart';
    const VALIDITY_END              = 'validityend';
    const STATUS                    = 'status';
}
