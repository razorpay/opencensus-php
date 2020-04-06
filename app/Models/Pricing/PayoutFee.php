<?php

namespace RZP\Models\Pricing;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Purpose;
use RZP\Models\Base\PublicEntity;

class PayoutFee extends Fee
{
    // This Plan ID is synced with Prod. It is used to assign a custom pricing to fee_recovery payouts.
    const ZERO_PRICING_PLAN_ID_RBL   = 'EDoLfqMMBHVYGR';

    protected function getCustomPricingPlan(PublicEntity $payout)
    {
        if ($payout->getPurpose() === Purpose::RZP_FEES)
        {
            return self::ZERO_PRICING_PLAN_ID_RBL;
        }

        return null;
    }

}
