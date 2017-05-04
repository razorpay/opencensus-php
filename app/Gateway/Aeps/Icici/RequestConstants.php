<?php

namespace RZP\Gateway\Aeps\Icici;

class RequestConstants
{
    const REVERSAL_MSG_TYPE     = '0400';
    const REQUEST_MSG_TYPE      = '0100';

    const OFFUS                 = 'OFFUS.APAY';
    const ONUS                  = 'SL.APAY';

    const MSG_TYPE              = '0';
    const ACC_NO                = '2';
    const REQ_TYPE              = '3';
    const AMOUNT                = '4';
    const COUNTER               = '11';
    const F22                   = '22';
    const F24                   = '24';
    const F25                   = '25';
    const F36                   = '36';
    const TERMINAL_ID           = '41';
    const F42                   = '42';
    const PID_BLOCK             = '60';
    const TRANS_TYPE            = '125';
    const FP_INFO               = '126';
    const EXTRA_BLOCK           = '127';
}
