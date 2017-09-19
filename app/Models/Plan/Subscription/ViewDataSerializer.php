<?php

namespace RZP\Models\Plan\Subscription;

use Config;

use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Customer;
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
    item:
            name
            description
            amount
            addons
     */

    /**
     * @var Entity
     */
    protected $subscription;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Customer\Entity
     */
    protected $customer;

    /**
     * @var Plan\Entity
     */
    protected $plan;

    /**
     * @var Card\Entity
     */
    protected $card;

    public function __construct(Entity $subscription)
    {
        parent::__construct();

        $this->subscription = $subscription;
        $this->merchant     = $subscription->merchant;
        $this->customer     = $subscription->customer;
        $this->plan         = $subscription->plan;
        $this->card         = $subscription->token->card;
    }

    /**
     * Returns view data (few formatted for view purpose)
     * of subscription, to be used in hosted page.
     *
     * @return array
     */
    public function get(): array
    {
        $subscriptionData = $this->getSubscriptionData();
        $merchantData = $this->getMerchantData();
        $customerData = $this->getCustomerData();
        $planData = $this->getPlanData();
        $cardData = $this->getCardData();
        $invoiceData = $this->getInvoiceData();

        $keyId = $this->repo
                      ->key
                      ->getKeysForMerchant($this->merchant->getId())
                      ->first()
                      ->getPublicKey($this->mode);

        return [
            'environment'   => $this->app->environment(),
            'mode'          => $this->mode,
            'key_id'        => $keyId,
            'merchant'      => $merchantData,
            'customer'      => $customerData,
            'subscription'  => $subscriptionData,
            'plan'          => $planData,
            'card'          => $cardData,
            'invoice'       => $invoiceData,
        ];
    }

    protected function getSubscriptionData(): array
    {
        $subscriptionData = [
            'id'    => $this->subscription->getPublicId(),
            'status'    => $this->subscription->getStatus(),
            'quantity'  => $this->subscription->getQuantity(),
            'charge_at' => $this->subscription->getChargeAt(),
        ];

        return $subscriptionData;
    }

    protected function getMerchantData(): array
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

        return $merchantData;
    }

    protected function getCustomerData(): array
    {
        $customerData = [
            'name'      => $this->customer->getName(),
            'email'     => $this->customer->getEmail(),
            'contact'   => $this->customer->getContact()
        ];

        return $customerData;
    }

    protected function getPlanData(): array
    {
        $planData = [
            'period'    => $this->plan->getPeriod(),
            'interval'  => $this->plan->getInterval(),
        ];

        return $planData;
    }

    protected function getCardData(): array
    {
        $cardData = [
            'bank'          => $this->card->getIssuer(),
            'last4'         => $this->card->getLast4(),
            'expires_at'    => $this->card->getExpiryTimestamp(),
        ];

        return $cardData;
    }

    protected function getInvoiceData(): array
    {
        $invoiceData = [

        ];

        return $invoiceData;
    }
}
