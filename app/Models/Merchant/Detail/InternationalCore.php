<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Models\Partner;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\InternationalActivationFlow\InternationalActivationFlow;

class InternationalCore extends Base\Core
{

    /** Activates international payments for a merchant
     *
     * @param Merchant\Entity $merchant
     */
    public function activateInternational(Merchant\Entity $merchant)
    {
        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $merchant->enableInternational();

        $merchant->setCurrencyConversion(false);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_INTERNATIONAL,
            [
                'action'      => 'activate',
                'category'    => $merchantDetails->getBusinessCategory(),
                'subcategory' => $merchantDetails->getBusinessSubCategory(),
            ]);

        $this->trace->count(Merchant\Metric::INTERNATIONAL_ACTIVATION);
    }

    /**
     * Deactivates international payments for a merchant
     *
     * @param Merchant\Entity $merchant
     */
    public function deactivateInternational(Merchant\Entity $merchant)
    {
        $merchant->disableInternational();

        $merchant->setCurrencyConversion(null);

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_INTERNATIONAL,
            [
                'action'      => 'deactivate',
                'category'    => $merchantDetails->getBusinessCategory(),
                'subcategory' => $merchantDetails->getBusinessSubCategory(),
            ]);
    }

    /** Returns international activation flow of a merchant
     *
     * @param Merchant\Entity      $merchant
     * @param Merchant\Entity|null $partner
     *
     * @return string
     * @throws \RZP\Exception\BadRequestException
     */
    public function getInternationalActivationFlow(Merchant\Entity $merchant, Merchant\Entity $partner = null)
    {
        $merchantDetails = (new Detail\Core())->getMerchantDetails($merchant);

        //
        // if submerchant asked for international and partner wants to force international to greylist
        //
        if ((new Partner\Core)->isForceGreylistMerchant($merchant, $partner) === true)
        {
            return InternationalActivationFlow::GREYLIST;
        }

        $subcategory = $merchantDetails->getBusinessSubcategory();
        $category    = $merchantDetails->getBusinessCategory();

        if (empty($category) === true)
        {
            return null;
        }

        $activationFlowFromCategoryDetails = BusinessSubCategoryMetaData::getFeatureValueUsingCategoryOrSubcategory(BusinessSubCategoryMetaData::INTERNATIONAL_ACTIVATION,
                                                                                                                    $category,
                                                                                                                    $subcategory);
        //
        // if Activation flow is blacklist then same should be used
        //
        if ($activationFlowFromCategoryDetails === InternationalActivationFlow::BLACKLIST)
        {
            return InternationalActivationFlow::BLACKLIST;
        }

        //
        // for Risky Business type by default do not enable international
        //
        if (BusinessType::isBusinessTypeGreylistedForInternational($merchantDetails->getBusinessType()) === true)
        {
            return InternationalActivationFlow::GREYLIST;
        }

        return $activationFlowFromCategoryDetails;
    }
}
