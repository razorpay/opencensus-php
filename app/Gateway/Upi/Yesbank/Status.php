<?php

namespace RZP\Gateway\Upi\Yesbank;

class Status
{
    const SUCCESS                    = 'S';
    const FAILURE                    = 'F';
    const TIMEOUT                    = 'T';
    const PENDING                    = 'P';
    const TXN_CREDIT_CONFIRM         = 'TCC';
    const REMITTER_RETURN_INITIATED  = 'RET';
    const REMITTER_RETURN_POSTED     =' RRC';
}
