<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\BadRequestValidationFailureException;

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
class Blacklist implements ActivationFlowInterface
{
    public function process(Entity $merchantDetails)
    {
        // TODO: Implement process() method.
    }

    /**
     * merchant with blacklist activation_flow are not allowed
     * to submit full activation form
     *
     * @param \RZP\Models\Merchant\Detail\Entity $merchantDetails
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        throw new BadRequestValidationFailureException(
            ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY);
    }
}
