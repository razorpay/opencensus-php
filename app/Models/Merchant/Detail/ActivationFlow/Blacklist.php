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
    /**
     * In blacklist activation flow , merchant won't get activated from basic (L1) activation form
     * These are unsupported category
     *
     * @param Entity $merchantDetails
     */
    public function process(Entity $merchantDetails)
    {
        return;
    }

    /**
     * Merchant with blacklist activation_flow are not allowed
     * To submit full activation form (L2 activation form)
     *
     * @param \RZP\Models\Merchant\Detail\Entity $merchantDetails
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateFullActivationForm(Entity $merchantDetails)
    {
        throw new BadRequestValidationFailureException(
            ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY,
            Entity::BUSINESS_SUBCATEGORY,
            [Entity::BUSINESS_SUBCATEGORY => $merchantDetails->getBusinessSubcategory(),]);
    }
}
