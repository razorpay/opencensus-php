<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Processor\DownstreamProcessor\Base as DSBase;

class Base extends DSBase
{
    protected function setChannel(Entity $payout)
    {
        $channel = snake_case(class_basename(get_called_class()));

        $payout->setChannel($channel);
    }
}
