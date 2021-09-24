<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Feature;
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
    const RAZORX_FOOTER_EXPERIMENT = 'pp_hostedpage_new_footer';


    const RAZORX_ASTERIX_EXPERIMENT = 'pp_hostedpage_asterisk';

    public function __construct(Entity $paymentLink)
    {
        parent::__construct();

        $this->paymentLink = $paymentLink;
        $this->merchant    = $paymentLink->merchant;
    }

    public function serializeForHosted(): array
    {
        return [
            'key_id'           => $this->getMerchantKeyId(),
            'is_test_mode'     => ($this->mode === Mode::TEST),
            'environment'      => $this->app->environment(),
            E::MERCHANT        => $this->serializeMerchantForHosted(),
            E::PAYMENT_LINK    => $this->serializePaymentLinkForHosted(),
            'base_url'         => $this->config['app']['url'],
            E::ORG             => $this->serializeOrgPropertiesForHosted(),
            'view_preferences' => $this->getViewPreferences(),
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
        $mode = $this->mode ?? Mode::LIVE;

        $footerVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            self::RAZORX_FOOTER_EXPERIMENT,
            $mode
        );

        $asterixVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            self::RAZORX_ASTERIX_EXPERIMENT,
            $mode
        );

        $contactOptional = $this->merchant->isFeatureEnabled(Feature\Constants::CONTACT_OPTIONAL);

        $emailOptional  = $this->merchant->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL);

        $supportDetails = $this->getMerchantSupportDetails();

        return [
            'id'               => $this->merchant->getId(),
            'name'             => $this->merchant->getBillingLabel(),
            'image'            => $this->merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'brand_color'      => get_rgb_value($this->merchant->getBrandColorOrOrgPreference()),
            'brand_text_color' => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
            'branding_variant' => 'control',
            'footer_variant'   => $footerVariant,
            'asterix_variant'  => $asterixVariant,
            'contact_optional' => $contactOptional,
            'email_optional'   => $emailOptional,
            'support_email'    => $supportDetails['support_email'],
            'support_mobile'   => $supportDetails['support_mobile'],
        ];
    }

    protected function getMerchantSupportDetails()
    {
        $supportDetails = $this->repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $this->merchant->getId());

        if ($supportDetails !== null)
        {
            $supportDetails = $supportDetails->toArrayPublic();

            return [
                'support_email'  => $supportDetails[Merchant\Email\Entity::EMAIL],
                'support_mobile' => $supportDetails[Merchant\Email\Entity::PHONE]
            ];
        }

        return ['support_email' => '', 'support_mobile' => ''];
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

    protected function serializeOrgPropertiesForHosted()
    {
        $org = $this->merchant->org;

        $branding = [
            'show_rzp_logo' => true,
            'branding_logo' => '',
        ];

        if($this->merchant->shouldShowCustomOrgBranding() === true)
        {
            switch ($org->getCustomCode())
            {
                case 'axis':

                    $branding['show_rzp_logo'] = false;

                    $branding['branding_logo'] = 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.png';

                    break;
            }
        }

        return [
            'branding'  => $branding
        ];
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

    protected function getViewPreferences(): array
    {
        $exemptCustomerFlagging = $this->merchant->isFeatureEnabled(Feature\Constants::APPS_EXEMPT_CUSTOMER_FLAGGING);

        $mode = $this->mode ?? Mode::LIVE;

        $pageLoadOptimizationEnabled = 'on';

        $disclaimerTextEnabled = 'on';

        return [
            'exempt_customer_flagging'       => $exemptCustomerFlagging,
            'page_load_optimization_enabled' => $pageLoadOptimizationEnabled,
            'disclaimer_text_enabled'        => $disclaimerTextEnabled,
            ];
    }
}
