<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use Config;
use RZP\Constants\Org;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Options;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\FileStore;
use RZP\Http\RequestHeader;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\PaperMandate;
use RZP\Models\Base\Utility;
use RZP\Constants\Entity as E;
use RZP\Models\Options\Constants;
use RZP\Models\Plan\Subscription;
use RZP\Models\Merchant\Preferences;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\PaymentLink\Template\UdfSchema;
use RZP\Models\UpiMandate\Metrics as UPIAutopayMetrics;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

/**
 * This class is common source of invoice and related data to be sent
 * - to mail templates as payload
 * - to hosted page view
 */
class ViewDataSerializerHosted extends Base\Core
{
    /**
     * {key}_formatted gets appended in view data
     * which holds the formatted time value for {key}
     *
     * @var array
     */
    protected static $epochs = [
        Entity::ISSUED_AT,
        Entity::DATE,
        Entity::EXPIRE_BY,
        Entity::EXPIRED_AT,
    ];

    /**
     * {key}_formatted gets appended in view data
     * which holds the formatted amount value for {key}
     *
     * @var array
     */
    protected static $amounts = [
        Entity::AMOUNT,
        Entity::AMOUNT_DUE,
        Entity::AMOUNT_PAID,
        Entity::TAX_AMOUNT,
        Entity::GROSS_AMOUNT,
    ];

    /**
     * @var Entity
     */
    protected $invoice;
    /**
     * @var Merchant\Entity
     */
    protected $merchant;
    /**
     * @var Options\Entity
     */
    protected $options;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice  = $invoice;
        $this->merchant = $invoice->merchant;
        $this->options  = new Options\Core();
        $this->core     = new Core();
    }

    public function serializeForHosted(): array
    {
        return [
            'environment'      => $this->app->environment(),
            'is_test_mode'     => ($this->mode === Mode::TEST),
            'invoicejs_url'    => Config::get('app.cdn_v1_url') . '/invoice.js',
            'key_id'           => $this->getMerchantKeyId(),
            'merchant'         => $this->serializeMerchantForHosted(),
            'invoice'          => $this->serializeInvoiceForHosted(),
            'custom_labels'    => $this->getCustomLabelValues(),
            'checkout_options' => $this->getCheckoutOptions(),
            'view_preferences' => $this->getViewPreferences(),
            E::ORG             => $this->serializeOrgPropertiesForHosted(),
        ];
    }

    public function serializeForHostedV2(): array
    {
        return [
            'environment'      => $this->app->environment(),
            'is_test_mode'     => ($this->mode === Mode::TEST),
            'invoicejs_url'    => Config::get('app.cdn_v1_url') . '/invoice.js',
            'key_id'           => $this->getMerchantKeyId(),
            'merchant'         => $this->serializeMerchantForHosted(),
            'invoice'          => $this->serializeInvoiceForHosted(),
            'options'          => $this->getOptions(),
            'view_preferences' => $this->getViewPreferences(),
            E::ORG             => $this->serializeOrgPropertiesForHosted(),
        ];
    }

    public function serializeForInternal(): array
    {
        $serialized = $this->serializeForHosted();

        $this->addAdditionalAttributesForInternal($serialized);

        return $serialized;
    }

    protected function serializeOrgPropertiesForHosted()
    {
        $org = $this->merchant->org;

        $merchantCountryCode = $this->merchant->getCountry();

        $branding = Org::ORG_BRANDING[$merchantCountryCode];
        $branding['bussiness_name'] = $org->getBusinessName() ?: Org::ORG_BRANDING[$merchantCountryCode][Org::BUSSINESS_NAME];
        $branding['branding_logo'] = $org->getInvoiceLogo()   ?: Org::ORG_BRANDING[$merchantCountryCode][Org::BRANDING_LOGO];

        if($this->merchant->shouldShowCustomOrgBranding() === true and $merchantCountryCode === 'IN')
        {
            $branding['show_rzp_logo'] = false;

            $branding['branding_logo'] = $org->getInvoiceLogo() ?: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.svg';

            $branding['bussiness_name'] = $org->getBusinessName() ?: 'Razorpay';
        }

        return [
            'branding'  => $branding
        ];
    }


    /**
     * Get view preferences for this Merchant
     * @return array
     */
    protected function getViewPreferences(): array
    {
        $hideIssuedTo = $this->merchant->isFeatureEnabled(Feature\Constants::PL_HIDE_ISSUED_TO);

        $exemptCustomerFlagging = $this->merchant->isFeatureEnabled(Feature\Constants::APPS_EXEMPT_CUSTOMER_FLAGGING);

        $viewPreferences = ['hide_issued_to' => $hideIssuedTo, 'exempt_customer_flagging' => $exemptCustomerFlagging];

        return $viewPreferences;
    }

    /**
     * Get custom view label values, if defined for the merchant
     * @return array
     */
    protected function getCustomLabelValues(): array
    {
        $merchantId = $this->merchant->getId();

        $customLabels = [
            'expire_by' => 'EXPIRES ON',
        ];

        switch ($merchantId)
        {
            case Preferences::MID_RBLCARD:
            case Preferences::MID_RBLBFL:
            case Preferences::MID_AMIT_RBLCARD:

                $customLabels = [
                    'receipt_number'           => 'CREDIT CARD NUMBER',
                    'first_payment_min_amount' => 'MAD', // I.e. Minimum Amount Due.
                ];

                break;

            case Preferences::MID_RBLLOAN:
            case Preferences::MID_DELINQUENT_LOANS:
            case Preferences::MID_AMIT_RBLLOAN:

                $customLabels = [
                    'receipt_number'           => 'LOAN ACCOUNT NUMBER',
                    'first_payment_min_amount' => 'EMI AMOUNT',
                ];

                break;

            case Preferences::MID_RBL_TOTAL_BASE:

                $customLabels = [
                    'receipt_number' => 'CREDIT CARD NUMBER',
                    'amount'         => 'TAD', // I.e. Total Amount Due.
                ];

                break;

            case Preferences::MID_RBLLENDING:
                $customLabels = [
                    'hide_issued_to' => true,
                ];

                break;

            case Preferences::MID_RBL_LENDING:
                $customLabels = [
                    'receipt_number' => 'LOAN ACCOUNT NUMBER',
                ];

                break;

            case Preferences::MID_SURYODAY_BANK:
                $customLabels = [
                    'receipt_number' => 'ACCOUNT NO',
                ];

                break;

            case Preferences::MID_BFL_BANK:
            case Preferences::MID_BFL_CARD:
            case Preferences::MID_RBL_PDD_CREDIT:
            case Preferences::MID_RBL_PDD_BANK:
                $customLabels = [
                    'receipt_number'            => 'CREDIT CARD NUMBER',
                    'amount'                    => 'TOTAL AMOUNT DUE',
                    'first_payment_min_amount'  => 'MAD', //I.e. Minimum Amount Due
                ];

                break;

            case Preferences::MID_RBL_LAPOD:
                $customLabels = [
                    'receipt_number'  =>  'LOAN ACCOUNT NUMBER',
                    'amount'          =>  'DROP AMOUNT',
                    'expire_by'       =>  'NEW LIMIT DATE',
                    'hide_issued_to'  =>  true,
                ];

                break;

            case Preferences::MID_RBL_PL_NON_DEL_CUST:
                $customLabels = [
                    'receipt_number'            =>  'LOAN ACCOUNT NUMBER',
                    'amount'                    =>  'TOTAL PAYABLE AMOUNT',
                    'expire_by'                 =>  'EMI DUE DATE',
                    'first_payment_min_amount'  =>  'EMI AMOUNT',
                ];

                break;

            case Preferences::MID_BOB:
                $customLabels = [
                    'amount'         => 'TOTAL AMOUNT DUE',
                    'expire_by'      => 'PAYMENT LINK EXPIRES ON',
                    'receipt_number' => 'CREDIT CARD NUMBER',
                ];
                break;

            case Preferences::MID_RBL_AGRI_LOAN:
                $customLabels = [
                    'amount'                    => 'TOTAL OVERDUE AMOUNT',
                    'receipt_number'            => 'LOAN ACCOUNT NUMBER',
                    'first_payment_min_amount'  => 'EMI AMOUNT',
                ];
                break;

            case Preferences::MID_RBL_BANK_2:
                $customLabels = [
                    'receipt_number'                    => 'Application Number',
                    'rb_bank_additional_description'    => true,
                ];
                break;

        }

        return $customLabels;
    }

    protected function getCheckoutOptions(): array
    {
        $merchantId = $this->merchant->getId();

        $checkoutOptions = ['description' => '#inv_'.$this->invoice->getId()];

        switch ($merchantId)
        {
            case Preferences::MID_SURYODAY_BANK:
                unset($checkoutOptions['description']);
        }
        return $checkoutOptions;
    }

    /**
     * @return string|null
     */
    protected function getMerchantKeyId()
    {
        return optional($this->repo->key->getFirstActiveKeyForMerchant($this->merchant->getId()))
            ->getPublicKey($this->mode);
    }

    protected function serializeMerchantForHosted(): array
    {
        $cin           = $this->merchant->getCompanyCin();
        $gstin         = $this->merchant->getGstin();
        $hasCinOrGstin = (($cin !== null) or ($gstin !== null));

        $partner = $this->merchant->getNonPurePlatformPartner();

        // Check if partner enforces its config on submerchant
        $overrideConfig = optional($partner)->isFeatureEnabled(Feature\Constants::OVERRIDE_SUB_CONFIG);

        if ($overrideConfig === true)
        {
            //
            // Checkout needs this to apply `remove border` for submerchants in cases where partner requires so (MSwipe)
            //
            // TODO: Ideally this ask is slightly more specific to MSwipe and the others like logo/theme overriding
            // are more generic and could be asked by/used for more merchants. When that happens, the check for this
            // block should move to a different feature.
            //
            $data['image_frame'] = false;
            $data['image_padding'] = false;
        }

        $brandImage = $this->getMerchantLogo($partner);

        $supportDetails = $this->getMerchantSupportDetails();

        // empty pan details as it might contain pII data
        // and reason for not removing the key is, it might be used in FE.
        $pan = '';

        return [
            'id'                               => $this->merchant->getId(),
            'name'                             => $this->invoice->getMerchantLabel(),
            'billing_label'                    => $this->invoice->getMerchantLabel(),
            'image'                            => $brandImage,
            'brand_logo'                       => $brandImage,
            'brand_color'                      => $this->getMerchantBrandColor($partner),
            'contrast_color'                   => $this->getMerchantContrastTextColor($partner),
            'brand_text_color'                 => $this->getMerchantBrandTextColor($partner),
            'pan'                              => $pan,
            'cin'                              => $cin,
            'gstin'                            => $gstin,
            'has_cin_or_gstin'                 => $hasCinOrGstin,
            'business_registered_address_text' => $this->merchant->getBusinessRegisteredAddressAsText(', '),
            'support_email'                    => $supportDetails['support_email'],
            'support_mobile'                   => $supportDetails['support_mobile'],
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

    protected function getMerchantLogo(Merchant\Entity $partner = null)
    {
        // Check if partner enforces its config on submerchant
        $overrideConfig = optional($partner)->isFeatureEnabled(Feature\Constants::OVERRIDE_SUB_CONFIG);

        $image = null;

        if ($overrideConfig === true)
        {
            $image = $partner->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE);
        }

        return $image ?: $this->merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE);
    }

    protected function getMerchantBrandColor(Merchant\Entity $partner = null): string
    {
        // Check if partner enforces its config on submerchant
        $overrideConfig = optional($partner)->isFeatureEnabled(Feature\Constants::OVERRIDE_SUB_CONFIG);

        $color = null;

        if ($overrideConfig === true)
        {
            $color = get_rgb_value($partner->getBrandColorOrDefault());
        }

        return $color ?: get_rgb_value($this->merchant->getBrandColorOrOrgPreference());
    }

    protected function getMerchantBrandTextColor(Merchant\Entity $partner = null): string
    {
        // Check if partner enforces its config on submerchant
        $overrideConfig = optional($partner)->isFeatureEnabled(Feature\Constants::OVERRIDE_SUB_CONFIG);

        $textColor = null;

        if ($overrideConfig === true)
        {
            $textColor = get_brand_text_color($partner->getBrandColorOrDefault());
        }

        return $textColor ?: get_brand_text_color($this->merchant->getBrandColorOrDefault());
    }

    protected function getMerchantContrastTextColor(Merchant\Entity $partner = null): string
    {
        // Check if partner enforces its config on submerchant
        $overrideConfig = optional($partner)->isFeatureEnabled(Feature\Constants::OVERRIDE_SUB_CONFIG);

        $textColor = null;

        if ($overrideConfig === true)
        {
            $textColor = $partner->getContrastOfBrandColor();
        }

        return $textColor ?: $this->merchant->getContrastOfBrandColor();
    }

    protected function serializeInvoiceForHosted(): array
    {
        //
        // Reload is needed as from Payment\Processor\Notify, the invoice
        // object passed as part of construct does not have relations loaded.
        //
        $this->repo->loadRelations($this->invoice);

        $this->app['basicauth']->setMerchant($this->invoice->merchant);

        $serialized = $this->invoice->toArrayHosted();

        $this->addDerivedAttributesForInvoice($serialized);

        $this->addFormattedAmountAttributesForInvoice($serialized);

        $this->addFormattedEpochAttributesForInvoice($serialized, $this->invoice->merchant);

        $this->addSubscriptionAttributesForInvoice($serialized);

        $this->addExternalEntityAttributesForInvoice($serialized, $this->invoice->merchant);

        return $serialized;
    }

    protected function addDerivedAttributesForInvoice(array & $serialized)
    {
        // In view, we show only captured(successful, not refunded) payments
        $serializedPayments = $this->repo->payment->getCapturedPaymentsForInvoice($this->invoice->getId());

        if($this->invoice->isTypeOfSubscriptionRegistration())
        {
            $billingLabelVariant = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::SHOW_BILLING_LABEL_OVER_MERCHANT_LABEL_FOR_RECURRING,
                $this->mode
            );

            $billingLabel = strtolower($billingLabelVariant) === 'on' ? $this->merchant->getBillingLabel() : $this->invoice->getMerchantLabel();
        }
        else
        {
            $billingLabel = $this->invoice->getMerchantLabel();
        }

        $serialized[Entity::IS_PAID]            = $this->invoice->isPaid();
        $serialized[Entity::PAYMENTS]           = $serializedPayments->toArrayHosted();
        $serialized[Entity::CALLBACK_URL]       = $this->invoice->getCallbackUrl();
        $serialized[Entity::CALLBACK_METHOD]    = $this->invoice->getCallbackMethod();
        $serialized[Entity::MERCHANT_GSTIN]     = $this->invoice->getMerchantGstin();
        $serialized[Entity::CUSTOMER_GSTIN]     = $this->invoice->getCustomerGstin();
        $serialized[Entity::MERCHANT_LABEL]     = $billingLabel;
        $serialized[Entity::SUPPLY_STATE_NAME]  = $this->invoice->getSupplyStateName();
        $serialized[Entity::HAS_ADDRESS_OR_POS] = (($this->invoice->hasCustomerBillingAddress() === true) or
            ($this->invoice->hasCustomerShippingAddress() === true) or
            ($this->invoice->getSupplyStateCode() !== null));

        $serialized[Entity::CUSTOMER_DETAILS] += [
            Entity::BILLING_ADDRESS_TEXT  => optional($this->invoice->customerBillingAddress)->formatAsText(),
            Entity::SHIPPING_ADDRESS_TEXT => optional($this->invoice->customerShippingAddress)->formatAsText(),
        ];

        // Unsets Customer details if invoice is in expired or paid status
        if ($this->invoice->isExpired() === true or $this->invoice->isPaid() === true)
        {
            $serialized[Entity::CUSTOMER_DETAILS] = '';
        }

        // Unsets customer_details
        if (!empty($serialized[Entity::CUSTOMER_DETAILS]) === true)
        {
            // make is_contact_or_email_present is true
            // so that it can be used to pass customer_id to checkout
            if (isset($serialized[Entity::CUSTOMER_DETAILS][Entity::CUSTOMER_CONTACT]) === true or
                isset($serialized[Entity::CUSTOMER_DETAILS][Entity::CUSTOMER_EMAIL]) === true)
            {
                $serialized[Entity::CUSTOMER_DETAILS][Entity::IS_CONTACT_OR_EMAIL_PRESENT] = true;
            }
            else
            {
                $serialized[Entity::CUSTOMER_DETAILS][Entity::IS_CONTACT_OR_EMAIL_PRESENT] = false;
            }

            $serialized[Entity::CUSTOMER_DETAILS][Entity::NAME] = '';
            $serialized[Entity::CUSTOMER_DETAILS][Entity::EMAIL] = '';
            $serialized[Entity::CUSTOMER_DETAILS][Entity::CONTACT] = '';

            $serialized[Entity::CUSTOMER_DETAILS][Entity::CUSTOMER_NAME] = '';
            $serialized[Entity::CUSTOMER_DETAILS][Entity::CUSTOMER_EMAIL] = '';
            $serialized[Entity::CUSTOMER_DETAILS][Entity::CUSTOMER_CONTACT] = '';
        }

        // Unsets Customer_id for all the status if FF 'skip_customer_id_checkout' is true
        $skipCustomerIdCheckout = $this->merchant->isFeatureEnabled(Feature\Constants::SKIP_CUSTOMER_ID_CHECKOUT);
        if ($skipCustomerIdCheckout === true and !empty($serialized[Entity::CUSTOMER_DETAILS]))
        {
            if (isset($serialized[Entity::CUSTOMER_DETAILS][Entity::ID]) === true)
            {
                $serialized[Entity::CUSTOMER_DETAILS][Entity::ID] = '';
            }
        }

        //
        // Additionally, if it's non-invoice and description is blank we fill it with first line item's description
        // else name. This is because for non-invoice, description should have been mandatory but for legacy reasons,
        // line items or description is expected. Only a few merchants have continued using it so and we are
        // communicating them to stop using it that way(deprecation). For now doing so doesn't require change in view,
        // mails etc and is UX wise is as expected.
        //
        if (($this->invoice->isNotTypeInvoice() === true) and (blank($serialized[Entity::DESCRIPTION]) === true))
        {
            $serialized[Entity::DESCRIPTION] = optional($this->invoice->lineItems->first())->getDescriptionElseName();
        }

        switch ($this->merchant->getId())
        {
            case Preferences::MID_RBL_RETAIL_ASSETS:
            case Preferences::MID_RBL_RETAIL_CUSTOMER:
            case Preferences::MID_RBL_RETAIL_PRODUCT:

                $serialized['rbl_emandate_retail_asset'] = true;

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS:

                $serialized['rbl_emandate_interim_process'] = true;

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS2:

                if ($this->invoice->getEntityType() === E::SUBSCRIPTION_REGISTRATION)
                {
                    $serialized['rbl_emandate_interim_process2'] = true;
                }

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS3:
            case Preferences::MID_RBL_INTERIM_PROCESS5:

                $serialized['rbl_emandate_interim_process3'] = true;

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS4:

                $serialized['rbl_emandate_interim_process4'] = true;

                break;
        }

        // adding signed pdf url also here. this can be used on checkout to show download pdf instead of using that id/pdf?download
        // end point. This serializer is used while generating pdf itself. so at that time pdf entry will be null

        if ($this->invoice->isTypeInvoice() === true)
        {
            $pdf = $this->invoice->pdf();

            $signedPdfUrl = null;

            if ($pdf === null)
            {
                $signedPdfUrl = $this->core->getFreshInvoicePdfFilePath($this->invoice);
            }
            else
            {
                $signedPdfUrl = (new FileUploadUfh())->getSignedUrl($this->invoice);
            }

            $serialized[Entity::SIGNED_PDF_URL] = $signedPdfUrl;
        }
    }

    protected function addFormattedAmountAttributesForInvoice(array & $serialized)
    {
        // Adds formatted invoice's amount attributes
        foreach (self::$amounts as $key)
        {
            $serialized[$key . '_formatted'] = amount_format_IN($serialized[$key]);
        }

        // Adds formatted invoice's line item's & their tax's amount attributes
        array_walk(
            $serialized[Entity::LINE_ITEMS],
            function (& $lineItem, $idx)
            {
                $lineItem += [
                    'amount_formatted'       => amount_format_IN($lineItem[LineItem\Entity::AMOUNT]),
                    'total_amount_formatted' => amount_format_IN($lineItem[LineItem\Entity::GROSS_AMOUNT]),
                    'has_taxes'              => (bool) $lineItem[LineItem\Entity::TAXES],
                ];

                array_walk(
                    $lineItem[LineItem\Entity::TAXES],
                    function (& $tax, $idx)
                    {
                        $tax += [
                            'tax_amount_formatted'  => amount_format_IN($tax[LineItem\Tax\Entity::TAX_AMOUNT]),
                        ];
                    });
            });
    }

    protected function addFormattedEpochAttributesForInvoice(array & $serialized, Merchant\Entity $merchant)
    {
        foreach (self::$epochs as $key)
        {
            $value = $serialized[$key];
            $formatted = ($value === null ? null : Carbon::createFromTimestamp($value, $merchant->getTimeZone())->format('j M Y'));
            $serialized[$key . '_formatted'] = $formatted;
        }
    }

    protected function addSubscriptionAttributesForInvoice(array & $serialized)
    {
        if ($this->invoice->isOfSubscription() === true)
        {
            $subscriptionId = $this->invoice->getSubscriptionId();

            $subscription = $this->app['module']
                ->subscription
                ->fetchSubscriptionForInvoice(
                    Subscription\Entity::getSignedId($subscriptionId),
                    $this->merchant
                );

            $serialized[E::SUBSCRIPTION] = $subscription;

            if ($this->invoice->getOfferAmount() !== null and $this->invoice->getOfferAmount() > 0)
            {
                $serialized[Entity::OFFER_AMOUNT . '_formatted'] = amount_format_IN($this->invoice->getOfferAmount());

                $comment = explode(';#$', $this->invoice->getComment());

                $serialized['offer_name'] = $comment[0];

                $serialized['offer_display_text'] = $comment[1] ?? '';
            }
        }
    }

    /**
     * Adds additional attributes ONLY to be used internally in various flows. E.g. merchant side mails, which requires
     * attributes besides hosted attributes, which is basically public user view attributes, etc.
     *
     * @param array $serialized
     */
    protected function addAdditionalAttributesForInternal(array & $serialized)
    {
        // Adds type label & dashboard path for invoices.
        $dashboardUrl = Config::get('applications.dashboard.url');
        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $serialized[E::INVOICE] += [
            'type_label'    => ucwords($this->invoice->getTypeLabel()),
            'dashboard_url' => $dashboardUrl . $invoiceDashboardPath,
        ];

        // Adds registered business address of merchant
        $serialized[E::MERCHANT] += [
            'business_registered_address' => optional($this->merchant->merchantDetail)->getBusinessRegisteredAddress(),
        ];

        // add label customizations
        switch ($this->merchant->getId()) {

            case Preferences::MID_BAGIC:

                $serialized['custom_labels']['expires_on'] = 'PAYMENT LINK EXPIRES ON';
                $serialized['custom_labels']['expired_on'] = 'PAYMENT LINK EXPIRED ON';
                $serialized['view_preferences']['hide_issued_to'] = true;

                break;


            case Preferences::MID_RBL_BANK:
            case Preferences::MID_RBL_BANK_LTD:
            case Preferences::MID_RBL_BANK_1:

                $serialized['custom_labels']['receipt_number'] = 'CREDIT CARD NUMBER';

                break;

            case Preferences::MID_GEPL_CAPITAL_PVT_LTD:
            case Preferences::MID_GEPL_CAPITAL_PVT_LTD_1:
            case Preferences::MID_GEPL_COMMODITIES_PVT_LTD:
            case Preferences::MID_GEPL_COMMODITIES_PVT_LTD_1:

                $serialized['view_preferences']['hide_issued_to'] = true;

                break;
        }
    }

    protected function addExternalEntityAttributesForInvoice(array & $serialized, Merchant\Entity $merchant)
    {
        $externalEntity = $this->invoice->entity;

        if ($this->invoice->isTypeOfSubscriptionRegistration() === true)
        {
            $order = $this->invoice->order;

            $serialized[E::SUBSCRIPTION_REGISTRATION] = $externalEntity->toArrayPublic();

            $serialized[Entity::ENTITY_TYPE] = E::SUBSCRIPTION_REGISTRATION;

            $expireAt = $serialized[E::SUBSCRIPTION_REGISTRATION][SubscriptionRegistration\Entity::EXPIRE_AT] ?? null;

            $formattedExpireAt = ($expireAt === null ? null :
                Carbon::createFromTimestamp($expireAt, $merchant->getTimeZone())->format('j M Y'));
            $serialized[E::SUBSCRIPTION_REGISTRATION]['expire_at_formatted'] = $formattedExpireAt;

            $serialized
            [E::SUBSCRIPTION_REGISTRATION]
            [E::PAYMENT] = $this->getNonFailurePaymentsForOrder($order);

            if ($externalEntity->getMethod() === SubscriptionRegistration\Method::UPI)
            {
                $serialized[E::SUBSCRIPTION_REGISTRATION]['frequency'] = $order->upiMandate['frequency'];

                $upiAutopayPromoIntentVariant = $this->app->razorx->getTreatment(
                    $order->getMerchantId(),
                    RazorxTreatment::UPI_AUTOPAY_PROMOTIONAL_INTENT,
                    $this->mode,
                    3
                );

                try
                {
                    $isUserAgentAndroid = str_contains(strtolower($this->app->request->header(RequestHeader::USER_AGENT)), 'android');

                    $successfulPayment = $this->getSuccessfulPaymentForOrder($order);

                    if (($isUserAgentAndroid === true) and
                        ($upiAutopayPromoIntentVariant === 'on') and
                        ($this->mode === 'live') and
                        ($successfulPayment === null))
                    {
                        $paymentRequest = [
                            Payment\Entity::AMOUNT      => $order->getAmount(),
                            Payment\Entity::CURRENCY    => $order->getCurrency(),
                            Payment\Entity::DESCRIPTION => 'Invoice #'.$this->invoice->getPublicId(),
                            Payment\Entity::EMAIL       => $this->invoice->getCustomerEmail(),
                            Payment\Entity::CONTACT     => $this->invoice->getCustomerContact(),
                            Payment\Entity::CUSTOMER_ID => $this->invoice->getPublicCustomerId(),
                            Payment\Entity::ORDER_ID    => $order->getPublicId(),
                            Payment\Entity::RECURRING   => '1',
                            Payment\Entity::METHOD      => 'upi',
                            'upi'                       => [
                                "flow" => "intent"
                            ]
                        ];

                        $paymentProcessor = new PaymentProcessor($order->merchant);

                        $paymentResponse = $paymentProcessor->process($paymentRequest);

                        if(empty($paymentResponse["data"]["intent_url"]) === false)
                        {
                            $serialized[E::SUBSCRIPTION_REGISTRATION]['upiAutopayPromoIntentUrl'] = $paymentResponse["data"]["intent_url"];

                            $this->trace->info(TraceCode::UPI_RECURRING_PROMOTIONAL_INTENT_PAYMENT_CREATED, [
                                'merchant_id' => $order->getMerchantId(),
                                'payment_id'  => $paymentResponse['payment_id'],
                                'order_id'    => $order->getId()
                            ]);

                            $this->trace->count(UPIAutopayMetrics::UPI_AUTOPAY_PROMOTIONAL_INTENT_PAYMENT_CREATED, [
                                    'merchant_id' => $order->getMerchantId()
                                ]);
                        }
                    }
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e);
                }
            }

            if ($externalEntity->getMethod() === SubscriptionRegistration\Method::EMANDATE)
            {
                $bankAccount = $externalEntity->entity;

                if ($bankAccount !== null)
                {
                    $serialized
                    [E::SUBSCRIPTION_REGISTRATION]
                    [E::BANK_ACCOUNT] = $bankAccount->toArrayHosted();
                }

                $serialized
                [E::SUBSCRIPTION_REGISTRATION]
                [E::BANK_ACCOUNT]
                [BankAccount\Entity::BANK_NAME] = $order->getBank();

                $serialized
                [E::SUBSCRIPTION_REGISTRATION]
                [E::ORDER]
                [Order\Entity::STATUS] = $order->getStatus();

            }
            else if ($externalEntity->getMethod() === SubscriptionRegistration\Method::NACH)
            {
                $paperMandate = $externalEntity->paperMandate;

                $startAt = $paperMandate->getStartAt() ?? null;

                $serialized
                [E::SUBSCRIPTION_REGISTRATION]
                [SubscriptionRegistration\Entity::NACH]
                [PaperMandate\Entity::START_AT] = $startAt;

                $serialized
                [E::SUBSCRIPTION_REGISTRATION]
                [SubscriptionRegistration\Entity::NACH]
                [PaperMandate\Entity::START_AT . '_formatted'] = $this->formatTime($startAt);

                $serialized
                [E::SUBSCRIPTION_REGISTRATION]
                [SubscriptionRegistration\Entity::NACH]
                [PaperMandate\Entity::IS_NACH_FORM_UPLOADED] = $paperMandate->isFormUploadedSuccessfully();
            }
        }
        elseif ($this->invoice->isPaymentPageInvoice() === true)
        {
            $serialized[Entity::ENTITY_TYPE] = E::PAYMENT_PAGE;

            $selectedInputFieldName = Settings\Accessor::for($externalEntity, Settings\Module::PAYMENT_LINK)
                ->get(PaymentLink\Entity::SELECTED_INPUT_FIELD);

            $seletcedInputFieldValue = $this->getSelectedInputFieldValue($selectedInputFieldName);

            $selectedInputFieldTitle = $this->getSelectedInputFieldTitleFromName($externalEntity, $selectedInputFieldName);

            $selectedInputField = [
                'label'   => $selectedInputFieldTitle,
                'value' => $seletcedInputFieldValue,
            ];

            $ppMerchantSettings = Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
                ->all();

            $details80g = [];

            $enable80g = Settings\Accessor::for($externalEntity, Settings\Module::PAYMENT_LINK)
                ->get(PaymentLink\Entity::ENABLE_80G_DETAILS);

            if ($enable80g !== null && $enable80g == "1")
            {
                $text80G = $ppMerchantSettings[PaymentLink\Entity::TEXT_80G_12A] ?? null;

                $imageURL80G = $ppMerchantSettings[PaymentLink\Entity::IMAGE_URL_80G] ?? null;

                $details80g = [
                    'text'      => $text80G,
                    'image_url' => $imageURL80G,
                ];

                $details80g = array_filter($details80g);
            }

            $title = $externalEntity->getAttribute(PaymentLink\Entity::TITLE);

            $viewType = $externalEntity->getAttribute(PaymentLink\Entity::VIEW_TYPE);

            $order = $this->invoice->order;

            $payment = $this->getCapturedPaymentForOrder($order);

            $paymentFormatted = [];

            if ($payment !== null)
            {
                $paymentFormatted = [
                    'id'                   => $payment->getId(),
                    'public_id'            => $payment->getPublicId(),
                    'amount'               => $payment->getFormattedAmount(),
                    'raw_amount'           => $payment['base_amount'],
                    'adjusted_amount'      => $payment->getAdjustedAmountWrtCustFeeBearer(),
                    'timestamp'            => $payment->getUpdatedAt(),
                    'captured_at'          => $payment->getAttribute('captured_at'),
                    'amount_spread'        => $payment->getAmountComponents(),
                    'created_at_formatted' => Utility::getTimestampFormattedByTimeZone($payment->getCreatedAt(), 'jS M, Y', $merchant->getTimeZone()),
                    'method'               => $payment->getMethodWithDetail(),
                    'notes'                => $payment->getNotes(),
                ];
            }

            $serialized[E::PAYMENT_PAGE] = [
                'enable_80g'           => $enable80g,
                'details_80g'          => empty($details80g) ? null : $details80g,
                'selected_input_field' => $selectedInputField,
                'title'                => $title,
                'payment'              => $paymentFormatted,
                'view_type'            => $viewType,
            ];
        }
        else
        {
            $serialized[Entity::ENTITY_TYPE] = null;
        }
    }

    protected function getSelectedInputFieldValue($selectedInputFiledName)
    {
        $order = $this->invoice->order;

        $payment = $this->repo->payment->fetchPaymentsForOrderId($order->getId())[0];

        $this->repo->loadRelations($payment);

        $notes = $payment->getNotes()->toArray();

        return $notes[$selectedInputFiledName] ?? null;
    }

    protected function getSelectedInputFieldTitleFromName($paymentPage, $selectedInputFieldName)
    {
        $udfSchemaClass = new UdfSchema($paymentPage);

        $udfSchemaJson = $udfSchemaClass->getSchema();

        $udfSchema = json_decode($udfSchemaJson);

        foreach ($udfSchema as $udf)
        {
            if($udf->name === $selectedInputFieldName)
            {
                return $udf->title;
            }
        }

        return '';
    }

    protected function getNonFailurePaymentsForOrder(Order\Entity $order)
    {
        $validPayments = [];

        if ($order === null)
        {
            return $validPayments;
        }

        $payments = $order->payments;

        if ($payments === null)
        {
            return $validPayments;
        }

        foreach ($payments as $payment)
        {
            if ($payment->getStatus() !== Payment\Status::FAILED)
            {
                array_push($validPayments,
                    [Payment\Entity::ID => $payment->getPublicId(),
                        Payment\Entity::STATUS => $payment->getStatus()]
                );
            }
        }

        return $validPayments;
    }

    protected function getCapturedPaymentForOrder(Order\Entity $order)
    {
        if ($order === null)
        {
            return null;
        }

        $payments = $order->payments;

        foreach ($payments as $payment)
        {
            if ($payment->getStatus() === Payment\Status::CAPTURED)
            {
                return $payment;
            }
        }

        return null;
    }

    protected function getSuccessfulPaymentForOrder(Order\Entity $order)
    {
        if ($order === null)
        {
            return null;
        }

        $payments = $order->payments;

        foreach ($payments as $payment)
        {
            if (($payment->getStatus() === Payment\Status::CAPTURED) or
                ($payment->getStatus() === Payment\Status::AUTHORIZED) or
                ($payment->getStatus() === Payment\Status::REFUNDED))
            {
                return $payment;
            }
        }

        return null;
    }

    protected function getOptions(): array
    {
        $options = $this->options->getMergedOptions(Constants::NAMESPACE_PAYMENT_LINKS,
            Constants::SERVICE_PAYMENT_LINKS,
            $this->invoice->getId(),
            $this->invoice->merchant->getId());

        return $options ?? [];
    }

    protected function formatTime($time, $format = 'j M Y')
    {
        if ($time === null)
        {
            return null;
        }

        return Carbon::createFromTimestamp($time, Timezone::IST)->format($format);
    }
}
