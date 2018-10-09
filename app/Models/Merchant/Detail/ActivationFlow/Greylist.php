<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant\Detail\Entity;

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
class Greylist implements ActivationFlowInterface
{
    public function process(Entity $merchantDetails)
    {
        // TODO: Implement process() method.
    }

    /**
     * validation specific to greylist activation flow
     *
     * @param \RZP\Models\Merchant\Detail\Entity $merchantDetails
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        return;
    }
}
