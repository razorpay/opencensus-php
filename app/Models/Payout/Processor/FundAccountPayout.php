<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Settlement;

class FundAccountPayout extends Base
{
    protected function setChannel($input = [])
    {
        $this->channel = Settlement\Channel::YESBANK;
    }
}
