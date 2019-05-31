<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;

class MerchantPayout extends Foundation\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->createTransaction($payout);

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }
}
