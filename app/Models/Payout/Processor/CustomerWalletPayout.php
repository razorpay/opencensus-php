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

    protected function fireEventForPayoutStatus(Payout\Entity $payout)
    {
        // TODO: Should deprecate api.payout.created webhook soon!
        // https://razorpay.atlassian.net/browse/RX-854
        $this->app->events->dispatch('api.payout.created', [$payout]);
        $this->app->events->dispatch('api.payout.initiated', [$payout]);
    }
}
