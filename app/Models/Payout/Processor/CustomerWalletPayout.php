<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout\Entity;
use RZP\Models\Settlement;
use RZP\Models\Payout\Core as PayoutCore;

class CustomerWalletPayout extends Base
{
    /**
     * Since we don't want to register beneficieries for all the merchants customers.
     * Yes bank will be used as channel.
     */
    public function setChannel()
    {
        $this->channel = Settlement\Channel::YESBANK;
    }

    public function setPayoutDestination($input)
    {
        $this->destination = (new PayoutCore)->getPayoutDestination($input, $this->merchant, $this->customer);
    }

    public function setCustomer(array $input)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $this->customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
    }

    public function createPayout(array $input)
    {
        $this->setCustomer($input);

        return parent::createPayout($input);
    }
}

