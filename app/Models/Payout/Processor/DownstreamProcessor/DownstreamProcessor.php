<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use Redis;

use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Balance\AccountType;

class DownstreamProcessor
{
    const CITI_CHANNEL_PAYOUT_MIDS  = 'citi_channel_payout_mids';

    protected $type;

    protected $payout;

    protected $ftaAccount;

    public function __construct(string $type, Entity $payout, PublicEntity $ftaAccount = null)
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

    public function processTransaction()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->processTransaction($this->payout);
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
        return $this->payout->balance->getAccountType() ?? AccountType::SHARED;
    }

    protected function getChannelForFundTransfer()
    {
        $merchant = $this->payout->merchant;

        if ($this->checkIfChannelShouldBeCiti($merchant) === true)
        {
            return Channel::CITI;

        }
        return $this->payout->balance->getChannel() ?? Channel::YESBANK;
    }

    // ToDo: Currently there is no proper way to decide the channel through
    // which the payout should be routed in case of shared accounts.
    // Till the time we achieve this by Dynamic routing, we are doing
    // a hack of using config key to store the Mids for which
    // channel for processing the payout should be CITI
    protected function checkIfChannelShouldBeCiti(Merchant $merchant)
    {
        $mid = $merchant->getId();

        $redis = Redis::connection();

        $isPresent = ($redis->sismember(self::CITI_CHANNEL_PAYOUT_MIDS, $mid) === 1) ? true : false;

        return $isPresent;
    }
}
