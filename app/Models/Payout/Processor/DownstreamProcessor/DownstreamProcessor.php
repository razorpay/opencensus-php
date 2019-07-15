<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;

class DownstreamProcessor
{
    protected $type;

    protected $payout;

    protected $ftaAccount;

    public function __construct(string $type, Entity $payout, PublicEntity $ftaAccount)
    {
        $this->type = $type;

        $this->payout = $payout;

        $this->ftaAccount = $ftaAccount;
    }

    public function process()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->process($this->payout, $this->ftaAccount);
    }

    protected function getSubProcessorClass()
    {
        $subProcessor = __NAMESPACE__ . '\\' . studly_case($this->type);

        if (snake_case($this->type) === 'fund_account_payout')
        {
            $accountType = $this->getAccountTypeForFundTransfer();

            $channel = $this->getChannelForFundTransfer();

            $subProcessor = $subProcessor . '\\' . studly_case($accountType) . '\\' . studly_case($channel);
        }

        return new $subProcessor;
    }

    protected function getAccountTypeForFundTransfer()
    {
        return $this->payout->balance->getAccountType() ?? 'shared';
    }

    protected function getChannelForFundTransfer()
    {
        return $this->payout->balance->getChannel() ?? Channel::YESBANK;
    }
}
