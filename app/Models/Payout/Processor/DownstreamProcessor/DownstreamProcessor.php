<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Payout\Entity;

class DownstreamProcessor
{
    public function process(string $type, Entity $payout, $ftaAccount): Entity
    {
        $subProcessor = $this->getSubProcessorClass($type, $payout);

        $payout = $subProcessor->process($payout, $ftaAccount);

        return $payout;
    }

    protected function getSubProcessorClass(string $type, Entity $payout)
    {
        $subProcessor = __NAMESPACE__ . '\\' . studly_case($type);

        if ($type === 'fund_account')
        {
            $accountType = $this->getAccountTypeForFundAccount($payout);

            $channel = $payout->getChannel();

            $subProcessor = $subProcessor . '\\' . studly_case($accountType) . '\\' . studly_case($channel);
        }

        return new $subProcessor;
    }

    protected function getAccountTypeForFundAccount(Entity $payout)
    {
        // TODO: Use constants and add logic once balance and account related changes are done for RBL.
        return 'shared';
    }

    protected function getBankForFundAccount(Entity $payout)
    {
        return $payout->getChannel();
    }
}
