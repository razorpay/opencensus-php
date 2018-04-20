<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
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
        Entity::EXPIRED_AT
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
        Entity::AMOUNT_PAID
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

    protected function getMerchantKeyId(): string
    {
        return $this->repo
                    ->key
                    ->getFirstActiveKeyForMerchantOrFail($this->merchant->getId())
                    ->getPublicKey($this->mode);
    }

    protected function serializeMerchantForHosted(): array
    {
        return [
            'new_view_enabled' => (in_array('Hostedplv2', $this->merchant->liveTagNames(), true) === true),
            'brand_color'      => get_rgb_value($this->merchant->getBrandColorOrDefault()),
            'brand_text_color' => get_brand_text_color($this->merchant->getBrandColorOrDefault()),
            'image'            => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'name'             => $this->merchant->getBillingLabel(),
            'id'               => $this->merchant->getId(),
        ];
    }

    protected function serializeInvoiceForHosted(): array
    {
        // Reload is needed as from Payment\Processor\Notify, the invoice
        // object passed as part of construct does not have relations loaded.
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
        // Ordered serialized payments of invoice
        $serializedPayments = $this->invoice
                                   ->load(Entity::PAYMENTS)
                                   ->payments
                                   ->sortByDesc(Entity::CREATED_AT)
                                   ->values()
                                   ->toArrayHosted();

        $serialized[Entity::IS_PAID]         = $this->invoice->isPaid();
        $serialized[Entity::PAYMENTS]        = $serializedPayments;
        $serialized[Entity::CALLBACK_URL]    = $this->invoice->getCallbackUrl();
        $serialized[Entity::CALLBACK_METHOD] = $this->invoice->getCallbackMethod();

        //
        // Additionally, if it's type=link and description is blank we fill it with first line item's description else
        // name. This is because for type=link, description should have been mandatory but for legacy reasons, line items
        // or description is expected. Only a few merchants have continued using it so and we are communicating them
        // to stop using it that way(deprecation). For now doing so doesn't require change in view, mails etc and is
        // UX wise is as expected.
        //
        if (($this->invoice->isTypeLink() === true) and (blank($serialized[Entity::DESCRIPTION]) === true))
        {
            $serialized[Entity::DESCRIPTION] = $this->invoice->lineItems->first()->getDescriptionElseName();
        }
    }

    protected function addFormattedAmountAttributesForInvoice(array & $serialized)
    {
        // Adds formatted invoice's amount attributes
        foreach (self::$amounts as $key)
        {
            $serialized[$key . '_formatted'] = number_format($serialized[$key] / 100, 2);
        }

        // Adds formatted invoice's line item's amount attributes
        array_walk(
            $serialized[Entity::LINE_ITEMS],
            function (& $lineItem, $idx)
            {
                $lineItem += [
                    'amount_formatted'       => number_format($lineItem[LineItem\Entity::AMOUNT] / 100, 2),
                    'total_amount_formatted' => number_format($lineItem[LineItem\Entity::GROSS_AMOUNT] / 100, 2),
                ];
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
