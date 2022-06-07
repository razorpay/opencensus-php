<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestException;
use RZP\Models\DeviceDetail\Constants as DDConstants;

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
class Blacklist extends Base implements ActivationFlowInterface
{
    /**
     * In blacklist activation flow , merchant won't get activated from basic (L1) activation form
     * These are unsupported category
     *
     * @param Entity $merchant
     */
    public function process(Entity $merchant)
    {
        $this->trace->info(TraceCode::MERCHANT_PROCESS_BLACKLIST_ACTIVATION);

        return;
    }

    /**
     * Merchant with blacklist activation_flow are not allowed
     * To submit full activation form (L2 activation form)
     *
     * @param Entity $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function validateFullActivationForm(Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === true)
        {
            $merchantDetails->setLocked(true);

            $merchant->deactivate();
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY,
                Detail\Entity::BUSINESS_SUBCATEGORY,
                [
                    Detail\Entity::BUSINESS_SUBCATEGORY => $merchantDetails->getBusinessSubcategory(),
                ]);
        }
    }
}
