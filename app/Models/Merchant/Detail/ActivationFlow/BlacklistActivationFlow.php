<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;
/**
 * Class BlacklistActivationFlow
 *
 * contains activation logic for blacklist activation flow
 * For Example :  Business category => FINANCIAL_SERVICES , Business SubCategory => CRYPTOCURRENCY
 * falls under blacklist activation flow
 * Detailed Mapping can be found here @Link @BusinessSubCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
class BlacklistActivationFlow implements ActivationFlowInterface
{
    public function process()
    {
        // TODO: Implement process() method.
    }
}
