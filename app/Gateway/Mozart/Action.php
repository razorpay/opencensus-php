<?php

namespace RZP\Gateway\Mozart;

class Action
{
    const PAY_INIT          = 'pay_init';
    const PAY_VERIFY        = 'pay_verify';

    const CAPTURE           = 'capture';
    const REFUND            = 'refund';
    const VERIFY            = 'verify';
    const VERIFY_REFUND     = 'verify_refund';

    const AUTHORIZE         = 'authorize';
}
