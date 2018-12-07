<?php

namespace RZP\Models\Payout\Processor;

class MerchantPayout extends Base
{
    protected function setChannel()
    {
        $this->channel = $this->merchant->getChannel();
    }

    protected function getDestinationId(array $input)
    {
        return $this->merchant->bankAccount->getPublicId();
    }
}
