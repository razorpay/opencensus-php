<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\QrCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends QrCode\Validator
{
    const MIN_CLOSE_BY_DIFF = 120;

    protected static $createRules = [
        Entity::REQ_PROVIDER   => 'required|in:bharat_qr,upi_qr',
        Entity::NAME           => 'sometimes|string',
        Entity::FIXED_AMOUNT   => 'required|boolean',
        Entity::REQ_AMOUNT     => 'required_if:fixed_amount,true|integer|min:1',
        Entity::REQ_USAGE_TYPE => 'required|in:single_use,multiple_use',
        Entity::DESCRIPTION    => 'sometimes|string|nullable',
        Entity::NOTES          => 'filled|notes',
        Entity::CUSTOMER_ID    => 'filled|string|nullable',
        Entity::CLOSE_BY       => 'filled|epoch|custom',
        Entity::TAX_INVOICE    => 'sometimes_if:type,upi_qr|array|custom',
        Entity::REQUEST_SOURCE => 'required'
    ];

    protected static $createForCheckoutRules = [
        Entity::ENTITY_ID      => 'required_with:entity_type|string',
        Entity::ENTITY_TYPE    => 'required_with:entity_id|in:order',
        Entity::REQ_AMOUNT     => 'sometimes|integer|min:1',
    ];

    protected static $taxInvoiceRules = [
        InvoiceDetails::INVOICE_DATE   => 'sometimes|integer',
        InvoiceDetails::INVOICE_NUMBER => 'sometimes|string',
        InvoiceDetails::CUSTOMER_NAME  => 'sometimes|string',
        InvoiceDetails::BUSINESS_GSTIN => 'sometimes|string',
        InvoiceDetails::SUPPLY_TYPE    => 'sometimes|string|in:interstate,intrastate',
        InvoiceDetails::CESS_AMOUNT    => 'sometimes|integer',
        InvoiceDetails::GST_AMOUNT     => 'sometimes|integer',
    ];

    public function validateTaxInvoice(string $attribute, array $taxInvoiceInput)
    {
        $this->validateInput('tax_invoice', $taxInvoiceInput);
    }

    public function validateCloseBy(string $attribute, int $closeBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::MIN_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }
}
