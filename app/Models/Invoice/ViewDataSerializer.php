<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant\Checkout;
use RZP\Models\LineItem;
use RZP\Exception;
use RZP\Constants\Mode;

class ViewDataSerializer extends Base\Core
{
    const DEFAULT_MERCHANT_BRAND_COLOR = '#6A5DD1';

    protected static $appendEpochsFormatted = [
        Entity::ISSUED_AT,
        Entity::DATE,
        Entity::EXPIRE_BY,
        Entity::EXPIRED_AT
    ];

    protected $invoice;
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
     * @throws Exception\BadRequestValidationFailureException
     */
    public function get(): array
    {
        $publicId = $this->invoice->getPublicId();

        if ($this->invoice->isDraft())
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invoice with id $publicId is not issued yet");
        }

        if ($this->invoice->isCancelled())
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invoice with id $publicId is cancelled");
        }

        $invoiceData = $this->getFormattedInvoiceDataForView();

        $keyId = $this->repo->key
                            ->getKeysForMerchant($this->merchant->getId())
                            ->first()
                            ->getPublicKey($this->mode);

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

    protected function getFormattedInvoiceDataForView(): array
    {
        $invoiceData = $this->invoice->toArrayPublic();

        $isInvoicePaid = $this->invoice->isPaid();
        $invoiceAmountFormatted = number_format($invoiceData[Entity::AMOUNT] / 100, 2);

        $invoiceData += [
            'is_paid'          => $isInvoicePaid,
            'amount_formatted' => $invoiceAmountFormatted,
        ];

        foreach (self::$appendEpochsFormatted as $key)
        {
            $epoch = $invoiceData[$key];

            if ($epoch === null)
            {
                $invoiceData[$key . '_formatted'] = null;
            }
            else
            {
                $formattedEpoch = Carbon::createFromTimestamp($epoch, 'Asia/Kolkata')
                                        ->format('j M Y');

                $invoiceData[$key . '_formatted'] = $formattedEpoch;
            }
        }

        array_walk(
            $invoiceData[Entity::LINE_ITEMS],
            function (& $lineItem, $i)
            {
                $amountFormatted = number_format($lineItem[LineItem\Entity::AMOUNT] / 100, 2);
                $totalAmount = $lineItem[LineItem\Entity::AMOUNT] * $lineItem[LineItem\Entity::QUANTITY];
                $totalAmountFormatted = number_format($totalAmount / 100, 2);

                $lineItem += [
                    'amount_formatted'       => $amountFormatted,
                    'total_amount_formatted' => $totalAmountFormatted,
                ];
            });

        return $invoiceData;
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
            'name'             => $this->merchant->getBillingLabelElseName(),
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
}
