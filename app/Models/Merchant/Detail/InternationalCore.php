<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Models\Merchant\Core;
use RZP\Models\Partner;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\Merchant\Detail;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Detail\InternationalActivationFlow\InternationalActivationFlow;

class InternationalCore extends Base\Core
{

    /**
     * @param Merchant\Entity $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     *
     * Activates international payments for a merchant
     */
    public function activateInternational(Merchant\Entity $merchant)
    {
        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $merchant->enableInternational();

        // Onboarding merchants to 3ds2 flow when international is activated
        (new Core)->checkAndPushMessageToMetroForNetworkOnboard($merchant->getId());

        // Added enable_3ds2 feature flags post pushing the message to metro
        (new Core)->addFeatureFlagForMerchant($merchant, Feature\Constants::ENABLE_3DS2);

        if ($this->getInternationalActivationFlow($merchant) === InternationalActivationFlow::WHITELIST
            and ($merchant->getOrgId() === Org::RAZORPAY_ORG_ID))
        {
            $merchant->enablePgInternational();
        }

        $merchant->setCurrencyConversion(false);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_INTERNATIONAL,
            [
                'action'      => 'activate',
                'category'    => $merchantDetails->getBusinessCategory(),
                'subcategory' => $merchantDetails->getBusinessSubCategory(),
            ]);

        $properties = [
            'source'      => 'BE',
            'action'      => 'International Payments Enabled',
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::INTERNATIONAL_PAYMENTS_ENABLED);

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

        //
        // is the selected subcategory is coming as greylist for public business type, then make is whitelist
        // #Experiment 1 for enablement of international activation flow
        //
        if($activationFlowFromCategoryDetails === InternationalActivationFlow::GREYLIST
            && BusinessType::PUBLIC_LIMITED === $merchantDetails->getBusinessType())
        {
            return InternationalActivationFlow::WHITELIST;
        }

        return $activationFlowFromCategoryDetails;
    }
}
