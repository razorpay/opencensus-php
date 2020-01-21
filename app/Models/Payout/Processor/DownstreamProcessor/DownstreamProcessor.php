<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Payout\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Payout\Processor\FundAccountPayout;

class DownstreamProcessor
{
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

        return $subProcessor->processTransaction($this->payout);
    }

    protected function getSubProcessorClass()
    {
        $subProcessor = __NAMESPACE__ . '\\' . studly_case($this->type);

        if (snake_case($this->type) === 'fund_account_payout')
        {
            $accountType = $this->getAccountTypeForFundTransfer();

            $channel = $this->payout->getChannel();

            $subProcessor = $subProcessor . '\\' . studly_case($accountType) . '\\' . studly_case($channel);
        }

        return new $subProcessor;
    }

    public function getAccountTypeForFundTransfer()
    {
        return $this->payout->balance->getAccountType() ?? AccountType::SHARED;
    }
}
