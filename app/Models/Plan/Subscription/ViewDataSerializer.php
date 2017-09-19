<?php

namespace RZP\Models\Plan\Subscription;

use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Checkout;

/**
 * This class is common source of subscription and related data to be sent
 * - to mail templates as payload
 * - to hosted page view
 *
 */
class ViewDataSerializer extends Base\Core
{
    const DEFAULT_MERCHANT_BRAND_COLOR = '#6A5DD1';

    /**
     * @var Entity
     */
    protected $subscription;
    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function __construct(Entity $subscription)
    {
        parent::__construct();

        $this->subscription = $subscription;
        $this->merchant     = $subscription->merchant;
    }

    /**
     * Returns view data (few formatted for view purpose)
     * of subscription, to be used in hosted page.
     *
     * @return array
     */
    public function get(): array
    {
        $subscriptionData = $this->getFormattedSubscriptionDataForView();

        $keyId = $this->repo
                      ->key
                      ->getKeysForMerchant($this->merchant->getId())
                      ->first()
                      ->getPublicKey($this->mode);

        $merchantData = $this->getFormattedMerchantDataForView();

        return [
            'environment'   => $this->app->environment(),
            // Following is sent to view for showing warning(in hosted page and
            // emails) to avoid mis communication.
            'is_test_mode'  => ($this->mode === Mode::TEST),
            'key_id'        => $keyId,
            'merchant'      => $merchantData,
            'subscription'  => $subscriptionData,
        ];
    }

    protected function getFormattedSubscriptionDataForView(): array
    {
        $this->repo->loadRelations($this->subscription);

        $subscriptionData = $this->subscription->toArrayPublic();

        $this->addExtraSubscriptionPayLoad($subscriptionData);

        return $subscriptionData;
    }

    protected function addExtraSubscriptionPayLoad(array & $data)
    {
        $id = $this->subscription->getPublicId();

        $subscriptionDashboardPath = $this->subscription->getDashboardPath();

        $dashboardUrl = Config::get('applications.dashboard.url');

        $extraSubscriptionPayload = [
            'dashboard_url' => $dashboardUrl . $subscriptionDashboardPath,
        ];

        $data += $extraSubscriptionPayload;
    }

    protected function getFormattedMerchantDataForView(): array
    {
        $merchantBrandColor = $this->merchant->getBrandColor();

        //
        // If brand_color is not set, use a default value.
        //
        if ($merchantBrandColor === null)
        {
            $merchantBrandColor = self::DEFAULT_MERCHANT_BRAND_COLOR;
        }

        $merchantData = [
            'brand_color'      => get_rgb_value($merchantBrandColor),
            'brand_text_color' => get_brand_text_color($merchantBrandColor),
            'image'            => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'name'             => $this->merchant->getBillingLabel(),
            'id'               => $this->merchant->getId(),
        ];

        if ($this->merchant->getOrgId() !== null)
        {
            $merchantData['organization'] = $this->merchant->org->toArrayPublic();
        }

        $merchantDetail = $this->merchant->merchantDetail;

        if ($merchantDetail !== null)
        {
            $merchantData['business_registered_address'] = $merchantDetail->getBusinessRegisteredAddress();
        }

        return $merchantData;
    }
}
