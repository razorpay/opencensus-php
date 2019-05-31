<?php

namespace RZP\Models\Payout\Processor;

use RZP\Constants;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Payout\Entity;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\Customer\Transaction\Core as CustTransactionCore;

class CustomerWalletPayout extends Base
{
    const DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION  = 'Debit wallet withdrawal fee amount';

    /**
     * Since we don't want to register beneficiaries for all the merchants customers.
     * Yes bank will be used as channel.
     *
     * @param array $input
     */
    protected function setChannel($input = [])
    {
        $this->channel = Settlement\Channel::YESBANK;
    }
}
