<?php

namespace RZP\Gateway\AxisMigs;

class Command
{
    const PAY       = 'pay';
    const CAPTURE   = 'capture';
    const REFUND    = 'refund';
    const QUERYDR   = 'queryDR';
    const REVERSAL  = 'voidAuthorisation';
}
