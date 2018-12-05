<?php

namespace RZP\Gateway\Hdfc\Payment;

class Action
{
    const PURCHASE  = 1;
    const REFUND    = 2;
    const AUTHORIZE = 4;
    const CAPTURE   = 5;
    const INQUIRY   = 8;
}
