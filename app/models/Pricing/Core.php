<?php

namespace Models\Pricing;

use Constants\Mode;
use Models\Base;
use Models\Merchant;
use Models\Pricing;
use Models\Terminal;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function checkPricingForAmex($merchant)
    {
        $plan = (new Pricing\Repository)->getMerchantPricingPlan($merchant);

        return ($plan->hasNetworkAmex());
    }

    public function hasWalletPricing($merchant)
    {
        $plan = (new Pricing\Repository)->getMerchantPricingPlan($merchant);

        return ($plan->hasMethodWallet());
    }
}
