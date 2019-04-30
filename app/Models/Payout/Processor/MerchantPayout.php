<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Exception\LogicException;
use RZP\Models\Settlement;

class MerchantPayout extends Base
{
    const DEFAULT_MERCHANT_PAYOUT_CHANNEL = Settlement\Channel::YESBANK;

    protected function setChannel($input = [])
    {
        $this->channel = $this->merchant->getChannel();
    }

    protected function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $destination = $this->merchant->bankAccount;

        //
        // On test mode, merchant->bankAccount gets created on signup.
        // On live, it is created during activation. Hence, there should be no case where
        // the on demand payout is called and the bankAccount does not exist.
        //
        if ($destination === null)
        {
            throw new LogicException(
                'Merchant bank account should exist for on demand payouts',
                null,
                [
                    'payout_id' => $payout->getId(),
                    'input'     => $input,
                ]);
        }

        $payout->destination()->associate($destination);

        $this->fundTransferDestination = $destination;
    }
}
