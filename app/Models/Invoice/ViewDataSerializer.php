<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Checkout;
use RZP\Models\Plan\Subscription;
use RZP\Exception\BadRequestException;

/**
 * This class is common source of invoice and related data to be sent
 * - to mail templates as payload
 * - to hosted page view
 */
class ViewDataSerializer extends Base\Core
{
    const DEFAULT_MERCHANT_BRAND_COLOR = '#6A5DD1';

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

    /**
     * Returns view data (few formatted for view purpose) of invoice,
     * to be used in hosted page, pdf generation, mails etc.
     *
     * @return array
     */
    public function get(): array
    {
        $invoiceData  = $this->getFormattedInvoiceDataForView();
        $keyId        = $this->getMerchantKeyId();
        $merchantData = $this->getFormattedMerchantDataForView();

        $invoiceJsUrl = Config::get('app.cdn_v1_url') . '/invoice.js';

        return [
            'environment'   => $this->app->environment(),
            // Following is sent to view for showing warning(in hosted page and
            // emails) to avoid mis communication.
            'is_test_mode'  => ($this->mode === Mode::TEST),
            'invoicejs_url' => $invoiceJsUrl,
            'key_id'        => $keyId,
            'merchant'      => $merchantData,
            'invoice'       => $invoiceData,
        ];
    }

    /**
     * Gets the view data long with few of subscription fields.
     * ViewDataSerializer gets used in multiple places and elsewhere we don't
     * need to load subscription relation of invoice. Only on hosted page (called
     * from Controller action) this is needed.
     *
     * @return array
     */
    public function getWithSubscriptionIfApplicable(): array
    {
        $data = $this->get();

        if ($this->invoice->isOfSubscription() === true)
        {
            $subscription = $this->invoice->subscription;

            $data[E::INVOICE][E::SUBSCRIPTION][Subscription\Entity::STATUS] = $subscription->getStatus();
        }

        return $data;
    }

    protected function getFormattedInvoiceDataForView(): array
    {
        // Reload is needed as from Payment\Processor\Notify, the invoice
        // object passed as part of construct does not have relations loaded.
        $this->repo->loadRelations($this->invoice);

        $invoiceData = $this->invoice->toArrayPublic();

        $invoiceData[Entity::IS_PAID] = $this->invoice->isPaid();

        // Puts callback_url, callback_method in view data. Those are not
        // exposed in route response as of now.

        $invoiceData[Entity::CALLBACK_URL]    = $this->invoice->getCallbackUrl();
        $invoiceData[Entity::CALLBACK_METHOD] = $this->invoice->getCallbackMethod();

        // Gets public view attributes of all payments against this invoice
        // in descending order.

        $invoiceData[Entity::PAYMENTS] = $this->invoice
                                              ->load(Entity::PAYMENTS)
                                              ->payments
                                              ->sortByDesc(Entity::CREATED_AT)
                                              ->values()
                                              ->toArrayHosted();

        foreach (self::$amounts as $key)
        {
            $invoiceData[$key . '_formatted'] = number_format($invoiceData[$key] / 100, 2);
        }

        foreach (self::$epochs as $key)
        {
            $epoch = $invoiceData[$key];

            $epochFormatted = null;

            if ($epoch !== null)
            {
                $epochFormatted = Carbon::createFromTimestamp($epoch, Timezone::IST)
                                        ->format('j M Y');
            }

            $invoiceData[$key . '_formatted'] = $epochFormatted;
        }

        array_walk(
            $invoiceData[Entity::LINE_ITEMS],
            function (& $lineItem, $i)
            {
                $amountFormatted      = number_format($lineItem[LineItem\Entity::AMOUNT] / 100, 2);
                $grossAmount          = $lineItem[LineItem\Entity::AMOUNT] * $lineItem[LineItem\Entity::QUANTITY];
                $grossAmountFormatted = number_format($grossAmount / 100, 2);

                $lineItem += [
                    'amount_formatted'       => $amountFormatted,
                    'total_amount_formatted' => $grossAmountFormatted,
                ];
            });

        $this->addExtraInvoicePayLoad($invoiceData);

        return $invoiceData;
    }

    protected function addExtraInvoicePayLoad(array & $data)
    {
        $id = $this->invoice->getPublicId();

        $invoiceDashboardPath = $this->invoice->getDashboardPath();

        $dashboardUrl = Config::get('applications.dashboard.url');

        $extraInvoicePayload = [
            'type_label'    => ucwords($this->invoice->getTypeLabel()),
            'pdf_url'       => url("v1/invoices/$id/pdf"),
            'dashboard_url' => $dashboardUrl . $invoiceDashboardPath,
        ];

        $data += $extraInvoicePayload;
    }

    protected function getFormattedMerchantDataForView(): array
    {
        $merchantBrandColor = $this->merchant->getBrandColor();

        //
        // If brand_color is not set, use a default value.
        // Same value is used in invoice.js (hosted page, pdf etc)
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

    protected function getMerchantKeyId(): string
    {
        $merchantId = $this->merchant->getId();

        $keys = $this->repo->key->getKeysForMerchant($merchantId);

        //
        // Currently key is being used in the view to open checkout and we server
        // bad request page if key is not available. Also, we restrict creation of
        // invoices as well when no key but there are some old invoices when the
        // restriction wasn't there during creation. So following check saves us
        // from server error.
        //
        if ($keys->count() === 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_API_KEY_NOT_PRESENT);
        }

        $keyId = $keys->first()->getPublicKey($this->mode);

        return $keyId;
    }
}
