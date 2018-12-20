<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Exception\LogicException;

class MerchantPayout extends Base
{
    protected function setChannel()
    {
        $this->channel = $this->merchant->getChannel();
    }

    public function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $destination = $this->merchant->bankAccount;

        //
        // On test mode, merchant->bankAccount gets created on signup.
        // On live, it is created during activation. Hence, there should be no case where
        // the on demand payout is called and the bankAccount does not exist.
        //
        if ($destination === null)
        {
            throw new LogicException('Merchant bank account should exist for on demand payouts');
        }

        $payout->destination()->associate($destination);

        $this->fundTransferDestination = $destination;
    }
}
