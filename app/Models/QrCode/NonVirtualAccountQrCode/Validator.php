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

    //64800 minutes or 45 days
    const MAX_CLOSE_BY_DIFF = 64800 * 60;

    protected static $createRules = [
        Entity::REQ_PROVIDER   => 'required|in:bharat_qr,upi_qr',
        Entity::NAME           => 'sometimes|custom',
        Entity::FIXED_AMOUNT   => 'required|boolean',
        Entity::REQ_AMOUNT     => 'required_if:fixed_amount,true|integer|min:1',
        Entity::REQ_USAGE_TYPE => 'required|in:single_use,multiple_use',
        Entity::DESCRIPTION    => 'sometimes|custom|nullable',
        Entity::NOTES          => 'filled|notes',
        Entity::CUSTOMER_ID    => 'filled|string|nullable',
        Entity::CLOSE_BY       => 'nullable|epoch|custom',
        Entity::TAX_INVOICE    => 'sometimes_if:type,upi_qr|array|custom',
        Entity::REQUEST_SOURCE => 'required',
    ];

    protected static $createForCheckoutRules = [
        Entity::CLOSE_BY       => 'filled|epoch|custom',
        Entity::CUSTOMER_ID    => 'filled|string',
        Entity::DESCRIPTION    => 'sometimes|custom',
        Entity::ENTITY_ID      => 'required_with:entity_type|string',
        Entity::ENTITY_TYPE    => 'required_with:entity_id|in:order,checkout_order',
        Entity::NAME           => 'sometimes|custom',
        Entity::NOTES          => 'filled|notes',
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

        $maxCloseBy = $now->copy()->addSeconds(self::MAX_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }

        if ($closeBy > $maxCloseBy->getTimestamp())
        {
            $message = 'QR expiry time cannot be more than ' . $maxCloseBy->diffInMinutes($now) . ' minutes from the current time';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateName($attribute, $value)
    {
        if (is_valid_utf8($value) === false)
        {
            $message = 'Only plain text characters are allowed';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateDescription($attribute, $value)
    {
        if (is_valid_utf8($value) === false)
        {
            $message = 'Only plain text characters are allowed';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateQrOnDedicatedTerminal($input)
    {
        if (($input['usage'] === UsageType::SINGLE_USE) and
            ($input[Entity::FIXED_AMOUNT] === false))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_DYNAMIC_QR_CODE_FIXED_AMOUNT_FAILURE);
        }

        if (($input['usage'] === UsageType::MULTIPLE_USE) and
            (isset($input[Entity::CLOSE_BY]) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_STATIC_QR_CODE_EXPIRY_FAILURE);
        }
    }
}
