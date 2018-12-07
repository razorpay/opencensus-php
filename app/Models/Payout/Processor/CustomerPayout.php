<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Settlement;

class ContactPayout extends Base
{
    protected function setChannel()
    {
        $this->channel = Settlement\Channel::YESBANK;
    }
}
