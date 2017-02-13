<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant\Checkout;
use RZP\Exception;

class ViewDataSerializer extends Base\Core
{
    protected $invoice;

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
     * @param string $mode
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function get(string $mode)
    {
        if ($this->invoice->isDraft())
        {
            $id = $this->invoice->getPublicId();

            throw new Exception\BadRequestValidationFailureException("Invoice with id $id is not issued yet");
        }

        $invoiceData = $this->getFormattedInvoiceDataForView();

        $keyId = $this->repo->key
                            ->getKeysForMerchant($this->merchant->getId())
                            ->first()
                            ->getPublicKey($mode);

        $merchantData = $this->getFormattedMerchantDataForView();

        $invoiceJsUrl = Config::get('app.cdn_v1_url') . '/invoice.js';

        return [
            'environment'   => $this->app->environment(),
            'invoicejs_url' => $invoiceJsUrl,
            'key_id'        => $keyId,
            'merchant'      => $merchantData,
            'invoice'       => $invoiceData,
        ];
    }

    protected function getFormattedInvoiceDataForView()
    {
        $invoiceData = $this->invoice->toArrayPublic();

        $invoiceData['is_paid'] = ($this->invoice->isPaid());
        $invoiceData['amount_formatted'] = number_format($invoiceData['amount']/100, 2);

        foreach ([Entity::ISSUED_AT, Entity::DATE] as $k)
        {
            $invoiceData[$k . '_formatted'] = Carbon::createFromTimestamp($invoiceData[$k], 'Asia/Kolkata')
                                                    ->format('j M Y');
        }

        array_walk(
            $invoiceData['line_items'],
            function (& $lineItem, $i)
            {
                $lineItem['amount_formatted'] = number_format($lineItem['amount'] / 100, 2);
                $lineItem['total_amount_formatted'] = number_format(($lineItem['amount'] * $lineItem['quantity']) / 100, 2);
            });

        return $invoiceData;
    }

    protected function getFormattedMerchantDataForView()
    {
        $merchantData = [
            'color' => $this->merchant->getBrandColor(),
            'image' => $this->merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'name'  => $this->merchant->getBillingLabelElseName(),
            'id'    => $this->merchant->getId(),
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
