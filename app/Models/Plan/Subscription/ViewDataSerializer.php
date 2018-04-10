<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Checkout;

/**
 * This class is common source of subscription and related data to be sent
 * - to mail templates as payload
 * - to hosted page view
 */
class ViewDataSerializer extends Base\Core
{
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
    public function serializeForHosted(): array
    {
        $serializedSubscription = $this->serializeSubscriptionForHosted();
        $serializedMerchant     = $this->serializeMerchantForHosted();
        $serializedCustomer     = $this->serializeCustomerForHosted();
        $serializedPlan         = $this->serializePlanForHosted();
        $serializedCard         = $this->serializeCardForHosted();
        $keyId                  = $this->getMerchantKeyId();

        $serializedForHosted = [
            'environment'  => $this->app->environment(),
            'mode'         => $this->mode,
            'key_id'       => $keyId,
            'merchant'     => $serializedMerchant,
            'customer'     => $serializedCustomer,
            'subscription' => $serializedSubscription,
            'plan'         => $serializedPlan,
            'card'         => $serializedCard,
        ];

        $this->trace->info(TraceCode::SUBSCRIPTION_VIEW_DATA_SERIALIZER_RESPONSE, $serializedForHosted);

        return $serializedForHosted;
    }

    protected function getMerchantKeyId(): string
    {
        return $this->repo
                    ->key
                    ->getFirstActiveKeyForMerchantOrFail($this->merchant->getId())
                    ->getPublicKey($this->mode);
    }

    protected function serializeSubscriptionForHosted(): array
    {
        $chargeAt          = $this->subscription->getChargeAtAttribute();
        $chargeAtFormatted = Carbon::createFromTimestamp($chargeAt, Timezone::IST)->format('d F Y');

        return [
            'id'                 => $this->subscription->getPublicId(),
            'status'             => $this->subscription->getStatus(),
            'quantity'           => $this->subscription->getQuantity(),
            'charge_at'          => $chargeAtFormatted,
            'card_change_status' => $this->subscription->isCardChangeStatus(),
            'card_change_amount' => (new Core)->getAuthTransactionAmountForCardChange($this->subscription),
            'addons'             => $this->repo->addon->getUnusedAddonsForSubscription($this->subscription),
        ];
    }

    protected function serializeMerchantForHosted(): array
    {
        return [
            'brand_color'      => get_rgb_value($this->merchant->getBrandColorOrDefault()),
            'brand_text_color' => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
            'image'            => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'name'             => $this->merchant->getBillingLabel(),
            'id'               => $this->merchant->getId(),
        ];
    }

    protected function serializeCustomerForHosted(): array
    {
        return [
            'name'    => $this->customer->getName(),
            'email'   => $this->customer->getEmail(),
            'contact' => $this->customer->getContact()
        ];
    }

    protected function serializePlanForHosted(): array
    {
        return [
            'period'   => $this->plan->getPeriod(),
            'interval' => $this->plan->getInterval(),
            'anchor'   => $this->subscription->schedule->getAnchor(),
            'item'     => $this->plan->item->toArrayPublic()
        ];
    }

    protected function serializeCardForHosted(): array
    {
        $expiresAt          = $this->card->getExpiryTimestamp();
        $expiresAtFormatted = Carbon::createFromTimestamp($expiresAt, Timezone::IST)->format('F Y');

        return [
            'bank'       => $this->card->getIssuer(),
            'last4'      => $this->card->getLast4(),
            'expires_at' => $expiresAtFormatted,
        ];
    }
}
