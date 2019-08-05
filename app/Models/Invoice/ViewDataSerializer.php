<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Preferences;
use RZP\Models\SubscriptionRegistration;

/**
 * This class is common source of invoice and related data to be sent
 * - to mail templates as payload
 * - to hosted page view
 */
class ViewDataSerializer extends Base\Core
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

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice  = $invoice;
        $this->merchant = $invoice->merchant;
    }

    public function serializeForHosted(): array
    {
        return [
            'environment'       => $this->app->environment(),
            'is_test_mode'      => ($this->mode === Mode::TEST),
            'invoicejs_url'     => Config::get('app.cdn_v1_url') . '/invoice.js',
            'key_id'            => $this->getMerchantKeyId(),
            'merchant'          => $this->serializeMerchantForHosted(),
            'invoice'           => $this->serializeInvoiceForHosted(),
            'custom_labels'     => $this->getCustomLabelValues(),
            'checkout_options'  => $this->getCheckoutOptions(),
        ];
    }

    public function serializeForInternal(): array
    {
        $serialized = $this->serializeForHosted();

        $this->addAdditionalAttributesForInternal($serialized);

        return $serialized;
    }

    /**
     * Get custom view label values, if defined for the merchant
     * @return array
     */
    protected function getCustomLabelValues(): array
    {
        $merchantId = $this->merchant->getId();

        $customLabels = ['hide_issued_to' => false];

        switch ($merchantId)
        {
            case Preferences::MID_RBLCARD:
            case Preferences::MID_RBLBFL:
            case Preferences::MID_AMIT_RBLCARD:

                $customLabels = [
                    'receipt_number'           => 'CREDIT CARD NUMBER',
                    'first_payment_min_amount' => 'MAD', // I.e. Minimum Amount Due.
                    'hide_issued_to'           => true,
                ];

                break;

            case Preferences::MID_RBLLOAN:
            case Preferences::MID_DELINQUENT_LOANS:
            case Preferences::MID_AMIT_RBLLOAN:

                $customLabels = [
                    'receipt_number'           => 'LOAN ACCOUNT NUMBER',
                    'first_payment_min_amount' => 'EMI AMOUNT',
                    'hide_issued_to'           => true,
                ];

                break;

            case Preferences::MID_RBL_TOTAL_BASE:

                $customLabels = [
                    'receipt_number' => 'CREDIT CARD NUMBER',
                    'amount'         => 'TAD', // I.e. Total Amount Due.
                    'hide_issued_to' => true,
                ];

                break;

            case Preferences::MID_RBLLENDING:
                $customLabels = [
                    'hide_issued_to' => true,
                ];

                break;

            case Preferences::MID_SURYODAY_BANK:
                $customLabels = [
                    'receipt_number' => 'ACCOUNT NO',
                ];

        }

        return $customLabels;
    }

    protected function getCheckoutOptions(): array
    {
        $merchantId = $this->merchant->getId();

        $checkoutOptions = ['description' => '#'.$this->invoice->getId()];

        switch ($merchantId)
        {
            case Preferences::MID_SURYODAY_BANK:
                $checkoutOptions = [
                    'description' => ''
                ];
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

        return [
            'id'                               => $this->merchant->getId(),
            'name'                             => $this->invoice->getMerchantLabel(),
            'image'                            => $this->getMerchantLogo($partner),
            'brand_color'                      => $this->getMerchantBrandColor($partner),
            'brand_text_color'                 => $this->getMerchantBrandTextColor($partner),
            'cin'                              => $cin,
            'gstin'                            => $gstin,
            'has_cin_or_gstin'                 => $hasCinOrGstin,
            'business_registered_address_text' => $this->merchant->getBusinessRegisteredAddressAsText(', '),
        ];
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

        return $color ?: get_rgb_value($this->merchant->getBrandColorOrDefault());
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

    protected function serializeInvoiceForHosted(): array
    {
        //
        // Reload is needed as from Payment\Processor\Notify, the invoice
        // object passed as part of construct does not have relations loaded.
        //
        $this->repo->loadRelations($this->invoice);

        $serialized = $this->invoice->toArrayHosted();

        $this->addDerivedAttributesForInvoice($serialized);

        $this->addFormattedAmountAttributesForInvoice($serialized);

        $this->addFormattedEpochAttributesForInvoice($serialized);

        $this->addSubscriptionAttributesForInvoice($serialized);

        $this->addExternalEntityAttributesForInvoice($serialized);

        return $serialized;
    }

    protected function addDerivedAttributesForInvoice(array & $serialized)
    {
        // In view, we show only captured(successful, not refunded) payments
        $serializedPayments = $this->invoice
                                   ->payments()
                                   ->status(Payment\Status::CAPTURED)
                                   ->get()
                                   ->toArrayHosted();

        $serialized[Entity::IS_PAID]            = $this->invoice->isPaid();
        $serialized[Entity::PAYMENTS]           = $serializedPayments;
        $serialized[Entity::CALLBACK_URL]       = $this->invoice->getCallbackUrl();
        $serialized[Entity::CALLBACK_METHOD]    = $this->invoice->getCallbackMethod();
        $serialized[Entity::MERCHANT_GSTIN]     = $this->invoice->getMerchantGstin();
        $serialized[Entity::CUSTOMER_GSTIN]     = $this->invoice->getCustomerGstin();
        $serialized[Entity::MERCHANT_LABEL]     = $this->invoice->getMerchantLabel();
        $serialized[Entity::SUPPLY_STATE_NAME]  = $this->invoice->getSupplyStateName();
        $serialized[Entity::HAS_ADDRESS_OR_POS] = (($this->invoice->hasCustomerBillingAddress() === true) or
                                                   ($this->invoice->hasCustomerShippingAddress() === true) or
                                                   ($this->invoice->getSupplyStateCode() !== null));

        $serialized[Entity::CUSTOMER_DETAILS] += [
            Entity::BILLING_ADDRESS_TEXT  => optional($this->invoice->customerBillingAddress)->formatAsText(),
            Entity::SHIPPING_ADDRESS_TEXT => optional($this->invoice->customerShippingAddress)->formatAsText(),
        ];

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

    protected function addFormattedEpochAttributesForInvoice(array & $serialized)
    {
        foreach (self::$epochs as $key)
        {
            $value = $serialized[$key];
            $formatted = ($value === null ? null : Carbon::createFromTimestamp($value, Timezone::IST)->format('j M Y'));
            $serialized[$key . '_formatted'] = $formatted;
        }
    }

    protected function addSubscriptionAttributesForInvoice(array & $serialized)
    {
        if ($this->invoice->isOfSubscription() === true)
        {
            $serialized[E::SUBSCRIPTION] = $this->invoice->subscription->toArrayHosted();
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
    }

    protected function addExternalEntityAttributesForInvoice(array & $serialized)
    {
        $externalEntity = $this->invoice->entity;

        if ($this->invoice->isTypeOfSubscriptionRegistration() === true)
        {
            $order = $this->invoice->order;

            $serialized[E::SUBSCRIPTION_REGISTRATION] = $externalEntity->toArrayPublic();

            $serialized[Entity::ENTITY_TYPE] = E::SUBSCRIPTION_REGISTRATION;

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
        }
        else
        {
            $serialized[Entity::ENTITY_TYPE] = null;
        }

    }
}
