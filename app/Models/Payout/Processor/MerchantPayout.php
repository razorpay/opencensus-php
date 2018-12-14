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
        $destinationId = $input[Payout\Entity::DESTINATION];

        if ($input[Payout\Entity::METHOD] === Payout\Method::FUND_TRANSFER)
        {
            $destination = $this->repo->bank_account->findByPublicIdAndMerchant($destinationId, $this->merchant);
        }
        else
        {
            $destination = $this->repo->vpa->findByPublicIdAndMerchant($destinationId, $this->merchant);
        }

        $payout->destination()->associate($destination);

        $this->fundTransferDestination = $destination;
    }
}
