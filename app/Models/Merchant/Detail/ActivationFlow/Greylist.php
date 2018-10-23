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
    /**
     * In greylist  activation flow , merchant won't get activated from basic activation form
     * Full activation form need to be filled for activation
     *
     * @param Entity $merchantDetails
     */
    public function process(Entity $merchantDetails)
    {
        return;
    }

    /**
     * Validation specific to the greylist activation flow
     *
     * @param Entity $merchantDetails
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        return;
    }
}
