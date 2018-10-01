<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

/**
 * Class GreylistActivationFlow
 *
 * contains activation logic for greylist activation flow
 * For Example :  Business category => NOT_FOR_PROFIT , Business SubCategory => CHARITY
 * fall under greylist activation flow
 * Detailed Mapping can be found here @Class BusinessSubCategoryMetaData
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
class GreylistActivationFlow implements ActivationFlowInterface
{
    public function process()
    {
        // TODO: Implement process() method.
    }
}
