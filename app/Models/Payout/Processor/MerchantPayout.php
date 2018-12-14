<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;

class MerchantPayout extends Base
{
    protected function setChannel()
    {
        $this->channel = $this->merchant->getChannel();
    }

    public function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $destination = $this->merchant->bankAccount;

        $payout->destination()->associate($destination);

        $this->fundTransferDestination = $destination;
    }
}
