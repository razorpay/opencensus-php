<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

/**
 * Class WhitelistActivationFlow
 *
 * Contains activation logic for whitelist international activation flow
 * For Example :  Business category => FINANCIAL_SERVICES , Business SubCategory => ACCOUNTING
 * fall under whitelist international activation flow
 * Detailed Mapping can be found here @Class BusinessCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\InternationalActivationFlow
 */
class Whitelist extends Base implements ActivationFlowInterface
{
    public function shouldActivateInternational(): bool
    {
        return true;
    }

    public function shouldActivateTypeformInternational(): bool
    {
        return true;
    }
}

