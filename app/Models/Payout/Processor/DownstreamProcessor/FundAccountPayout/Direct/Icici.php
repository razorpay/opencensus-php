<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Status;
use RZP\Trace\TraceCode;

class Icici extends Base
{

    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

        $holdPayout = $this->holdPayoutIfApplicableAndBeneBankDown($payout);

        if ($holdPayout === true)
        {
            return;
        }

        $queued = $this->queueIfLowBalance($payout);

        if ($queued === true)
        {
            return;
        }

        $this->assignFreePayoutIfApplicable($payout);

        $this->setFeeAndTaxForPayout($payout);

        $fta = $this->repo
                    ->fund_transfer_attempt
                    ->getFTSAttemptBySourceId($payout->getId(),
                                              'payout',
                                              true);

        if($fta === null)
        {
          $this->createFundTransferAttempt($payout, $ftaAccount);
        }
    }

    public function processIcici2FAPayout(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

        //This is required because for icici ca transfer request is sent to bank,
        // but fees and taxes will be set in a callback by when transfer can be successful.
        //Any errors in pricing when we process callback will be an issue.
        //So calculating this here and not setting it in payout right now to prevent unknown errors later
        $this->calculateFeesAndTaxForPayouts($payout);

        $payout->setSyncFtsFundTransferFlag(true);

        $fta = $this->repo
            ->fund_transfer_attempt
            ->getFTSAttemptBySourceId($payout->getId(),
                'payout',
                true);

        if($fta === null)
        {
            $this->createFundTransferAttempt($payout, $ftaAccount);
        }
    }

    public function handleFeeAndTaxForIcici2FACurrentAccountPayout(Entity $payout)
    {
        $this->assignFreePayoutIfApplicable($payout);

        $this->setFeeAndTaxForPayout($payout);
    }
}
