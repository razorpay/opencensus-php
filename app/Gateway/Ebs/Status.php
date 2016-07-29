<?php

namespace RZP\Gateway\Ebs;

class Status
{
    const SUCCESS                   = '0';

    const CREATED                   = 'created';
    const REFUNDED                  = 'refunded';
    const AUTHORIZED                = 'authorized';
    const REFUND_FAILED             = 'refund_failed';
    const AUTHORIZE_FAILED          = 'authorize_failed';
}
