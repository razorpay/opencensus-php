<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Checkout;

class ViewSerializer extends Base\Core
{
    /**
     * @var array
     */
    protected static $epochs = [
        Entity::EXPIRE_BY,
    ];

    /**
     * @var array
     */
    protected static $amounts = [
        Entity::AMOUNT,
    ];

    /**
     * @var Entity
     */
    protected $paymentLink;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function __construct(Entity $paymentLink)
    {
        parent::__construct();

        $this->paymentLink = $paymentLink;
        $this->merchant    = $paymentLink->merchant;
    }

    public function serializeForHosted(): array
    {
        return [
            'key_id'         => $this->getMerchantKeyId(),
            'is_test_mode'   => ($this->mode === Mode::TEST),
            'environment'    => $this->app->environment(),
            E::MERCHANT      => $this->serializeMerchantForHosted(),
            E::PAYMENT_LINK  => $this->serializePaymentLinkForHosted(),
        ];
    }

    public function serializeForInternal(): array
    {
        $serialized = $this->serializeForHosted();

        $this->addAdditionalAttributesForInternal($serialized);

        return $serialized;
    }

    /**
     * Returns settings array for given payment link with defaults.
     * @return array
     */
    public function serializeSettingsWithDefaults(): array
    {
        $settings = $this->paymentLink->getSettings()->toArray();

        // Prepends default UDF schema for view.
        $defaultUdfSchemaForView = $this->getDefaultUdfSchemaForView();
        $udfSchema = json_decode($settings[Entity::UDF_SCHEMA] ?? '{}', true);
        array_unshift($udfSchema, ...$defaultUdfSchemaForView);
        $settings[Entity::UDF_SCHEMA] = json_encode($udfSchema);

        // Puts other settings defaults
        $settings += [Entity::THEME => Entity::DEFAULT_THEME];

        return $settings;
    }

    /**
     * @return string|null
     */
    protected function getMerchantKeyId()
    {
        $key = $this->repo->key->getFirstActiveKeyForMerchant($this->merchant->getId());

        return optional($key)->getPublicKey($this->mode);
    }

    protected function serializeMerchantForHosted(): array
    {
        return [
            'id'               => $this->merchant->getId(),
            'name'             => $this->merchant->getBillingLabel(),
            'image'            => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'brand_color'      => get_rgb_value($this->merchant->getBrandColorOrDefault()),
            'brand_text_color' => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
        ];
    }

    protected function serializePaymentLinkForHosted(): array
    {
        $serialized = $this->paymentLink->toArrayHosted();

        $this->addAdditionalAttributesForPaymentLink($serialized);
        $this->addDerivedAttributesForPaymentLink($serialized);
        $this->addFormattedAmountAttributesForPaymentLink($serialized);
        $this->addFormattedEpochAttributesForPaymentLink($serialized);
        $this->addSettingsOfPaymentLink($serialized);

        return $serialized;
    }

    protected function addAdditionalAttributesForPaymentLink(array & $serialized)
    {
        $serialized[Entity::HOSTED_TEMPLATE_ID] = $this->paymentLink->getHostedTemplateId();
        $serialized[Entity::UDF_JSONSCHEMA_ID]  = $this->paymentLink->getUdfJsonschemaId();
    }

    protected function addDerivedAttributesForPaymentLink(array & $serialized)
    {
    }

    protected function addFormattedAmountAttributesForPaymentLink(array & $serialized)
    {
        foreach (self::$amounts as $key)
        {
            $serialized[$key . '_formatted'] = amount_format_IN($serialized[$key]);
        }
    }

    protected function addFormattedEpochAttributesForPaymentLink(array & $serialized)
    {
        foreach (self::$epochs as $key)
        {
            $value     = $serialized[$key];
            $formatted = ($value === null ? null : Carbon::createFromTimestamp($value, Timezone::IST)->format('j M Y'));

            $serialized[$key . '_formatted'] = $formatted;
        }
    }

    protected function addSettingsOfPaymentLink(array & $serialized)
    {
        if ($this->merchant->isTagAdded(Entity::TAG_PAYMENT_PAGE_V2) === true)
        {
            $serialized[Entity::SETTINGS] = $this->serializeSettingsWithDefaults();
        }
    }

    /**
     * Adds additional attributes ONLY to be used internally in various flows. E.g. merchant side mails, which requires
     * attributes besides hosted attributes, which is basically public user view attributes, etc.
     * @param array $serialized
     */
    protected function addAdditionalAttributesForInternal(array & $serialized)
    {
        $serialized[E::MERCHANT] += [
            'business_registered_address' => optional($this->merchant->merchantDetail)->getBusinessRegisteredAddress(),
        ];
    }

    /**
     * Hosted view expects email and phone(otherwise part of checkout modal) also for view rendering besides the
     * additional UDFs defined by merchant. We just prepends it here for view.
     * @return array
     */
    protected function getDefaultUdfSchemaForView(): array
    {
        return [
            [
                'title'    => 'Email',
                'name'     => 'email',
                'type'     => 'string',
                'pattern'  => 'email',
                'required' => true,
                'options'  => [
                    'keydown_restrictive' => false,
                ],
            ],
            [
                'title'     => 'Phone',
                'name'      => 'phone',
                'type'      => 'number',
                'pattern'   => 'phone',
                'required'  => true,
                'minLength' => 8,
                'options'   => [
                    'keydown_restrictive' => false,
                ],
            ],
        ];
    }
}
