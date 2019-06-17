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

        // TODO: For direct, we need to pass FTA Account ID for the source.
        // Changes to be done after FTA and account module changes are done.

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }
}
