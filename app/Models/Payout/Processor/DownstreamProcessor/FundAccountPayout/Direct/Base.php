<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }
}
