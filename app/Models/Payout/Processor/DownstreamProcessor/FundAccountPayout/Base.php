<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Processor\DownstreamProcessor\Foundation;

class Base extends Foundation\Base
{
    public function setChannel(Entity $payout)
    {
        $channel = snake_case(class_basename(get_called_class()));

        $payout->setChannel($channel);
    }
}
