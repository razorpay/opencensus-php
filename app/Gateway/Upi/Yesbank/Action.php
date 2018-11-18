<?php

namespace RZP\Gateway\Upi\Yesbank;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const PAYOUT         = 'payout';
    const PAYOUT_VERIFY  = 'payout_verify';
}
