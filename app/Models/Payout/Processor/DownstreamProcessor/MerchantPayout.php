<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Payout\Entity;

class MerchantPayout extends Foundation\Base
{
    public function setChannel(Entity $payout)
    {
        $channel = $payout->merchant->getChannel();

        $payout->setChannel($channel);
    }
}
