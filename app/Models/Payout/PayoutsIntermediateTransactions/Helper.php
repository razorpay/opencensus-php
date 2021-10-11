<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use RZP\Models\Base;
use RZP\Models\Payout\Entity as PayoutEntity;

class Helper extends Base\Core
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function markIntermediateTransactionReversedAndIncrementBalance(PayoutEntity $payout)
    {
        $this->core->markIntermediateTransactionReversedForPayout($payout);
    }
}
