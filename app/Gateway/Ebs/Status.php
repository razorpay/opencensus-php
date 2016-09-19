<?php

namespace RZP\Gateway\Ebs;

class Status
{
    const SUCCESS                   = '0';
    const API_SUCCESS               = 'SUCCESS';

    const CREATED                   = 'created';
    const REFUNDED                  = 'refunded';
    const AUTHORIZED                = 'authorized';
    const REFUND_FAILED             = 'refund_failed';
    const AUTHORIZE_FAILED          = 'authorize_failed';

    const API_AUTHORIZED            = 'Authorized';
    const API_AUTHORIZE_FAILED      = 'AuthFailed';
    const API_AUTHORIZE_INCOMPLETE  = 'Incompleted';
    const API_PROCESSING            = 'Processing';

}
