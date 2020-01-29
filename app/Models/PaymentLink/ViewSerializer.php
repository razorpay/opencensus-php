<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;

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
            'base_url'       => $this->config['app']['url'],
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

        if (empty($defaultUdfSchemaForView) === false)
        {
            array_unshift($udfSchema, ...$defaultUdfSchemaForView);
        }

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
            'image'            => $this->merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'brand_color'      => get_rgb_value($this->merchant->getBrandColorOrDefault()),
            'brand_text_color' => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
        ];
    }

    protected function serializePaymentLinkForHosted(): array
    {
        $this->repo->loadRelations($this->paymentLink);

        $serialized = $this->paymentLink->toArrayHosted();

        $this->addAdditionalAttributesForPaymentLink($serialized);
        $this->addDerivedAttributesForPaymentLink($serialized);
        $this->addFormattedAmountAttributesForPaymentLink($serialized);
        $this->addFormattedEpochAttributesForPaymentLink($serialized);
        $this->addSettingsOfPaymentLink($serialized);
        $this->serializePaymentPageItems($serialized);

        return $serialized;
    }

    protected function serializePaymentPageItems(array & $paymentLink)
    {
        if (isset($paymentLink[Entity::PAYMENT_PAGE_ITEMS]) === false)
        {
            return;
        }

        $PPICore = new PaymentPageItem\Core;

        for ($i = 0; $i < count($paymentLink[Entity::PAYMENT_PAGE_ITEMS]); $i++)
        {
            $paymentPageItem = $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i];

            $paymentPageItem = $PPICore->fetch($paymentPageItem[PaymentPageItem\Entity::ID], $this->paymentLink->merchant);

            $paymentPageItem->settings = $paymentPageItem->getSettings();

            $paymentPageItemSerialized = $paymentPageItem->toArrayHosted();

            $paymentPageItemSerialized['quantity_available'] = $paymentPageItem->getQuantityAvailable();

            $this->serializePPItemSettings($paymentPageItemSerialized);

            $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i] = $paymentPageItemSerialized;
        }
    }

    protected function serializePPItemSettings(array & $paymentPageItemSerialized)
    {
        $settings = $paymentPageItemSerialized[PaymentPageItem\Entity::SETTINGS];

        $paymentPageItemSerialized[PaymentPageItem\Entity::SETTINGS] = $settings->toArray();
    }

    protected function addAdditionalAttributesForPaymentLink(array & $serialized)
    {
        $serialized[Entity::HOSTED_TEMPLATE_ID] = $this->paymentLink->getHostedTemplateId();
        $serialized[Entity::UDF_JSONSCHEMA_ID]  = $this->paymentLink->getUdfJsonschemaId();
    }

    protected function addDerivedAttributesForPaymentLink(array & $serialized)
    {
        $serialized['min_amount_value'] = Currency::getMinAmount($this->paymentLink->getCurrency());

        $serialized['amount'] = $this->paymentLink->getAmountToSendSmsOrEmail();
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
        $serialized[Entity::SETTINGS] = $this->serializeSettingsWithDefaults();
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
        if ($this->paymentLink->getVersion() === Version::V2)
        {
            return [];
        }

        return [
            [
                'title'    => 'Email',
                'name'     => 'email',
                'type'     => 'string',
                'pattern'  => 'email',
                'required' => true,
                'settings' => [
                    'position' => 1,
                ],
            ],
            [
                'title'     => 'Phone',
                'name'      => 'phone',
                'type'      => 'number',
                'pattern'   => 'phone',
                'required'  => true,
                'minLength' => '8',
                'options'   => [],
                'settings' => [
                    'position' => 2,
                ],
            ],
        ];
    }
}
