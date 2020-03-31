<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

/**
 * Class BlacklistInternationalActivationFlow
 *
 * Contains international activation logic for blacklist international activation flow
 * For Example :  Business category => FINANCIAL_SERVICES , Business SubCategory => CRYPTOCURRENCY
 * falls under blacklist international activation flow
 * Detailed Mapping can be found here @Link @BusinessSubCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\InternationalActivationFlow
 */
class Blacklist extends Base implements ActivationFlowInterface
{
    public function shouldActivateInternational(): bool
    {
        return false;
    }

    public function shouldActivateTypeformInternational(): bool
    {
        return false;
    }
}
