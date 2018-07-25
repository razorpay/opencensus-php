<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Checkout;
use RZP\Exception\BadRequestException;

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
            'environment'   => $this->app->environment(),
            'is_test_mode'  => ($this->mode === Mode::TEST),
            'invoicejs_url' => Config::get('app.cdn_v1_url') . '/invoice.js',
            'key_id'        => $this->getMerchantKeyId(),
            'merchant'      => $this->serializeMerchantForHosted(),
            'invoice'       => $this->serializeInvoiceForHosted(),
        ];
    }

    public function serializeForInternal(): array
    {
        $serialized = $this->serializeForHosted();

        $this->addAdditionalAttributesForInternal($serialized);

        return $serialized;
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

        return [
            'id'                               => $this->merchant->getId(),
            'name'                             => $this->invoice->getMerchantLabel(),
            'image'                            => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'brand_color'                      => get_rgb_value($this->merchant->getBrandColorOrDefault()),
            'brand_text_color'                 => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
            'cin'                              => $cin,
            'gstin'                            => $gstin,
            'has_cin_or_gstin'                 => $hasCinOrGstin,
            'business_registered_address_text' => $this->merchant->getBusinessRegisteredAddressAsText(', '),
        ];
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
}
