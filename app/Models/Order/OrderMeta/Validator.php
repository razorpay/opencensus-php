<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Base;
use RZP\Models\Order\OrderMeta\TaxInvoice\Fields;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 * @package RZP\Models\Order\OrderMeta
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ORDER_ID    => 'required|string|size:14',
        Entity::TYPE        => 'required|string|custom',
        Entity::VALUE       => 'sometimes',
    ];

    /* Refer - https://docs.google.com/spreadsheets/d/1efKOeRykRVfdstqJAi9URd-CvC4XptldgiBbbKhmukA/edit#gid=1767878367*/
    protected static $createTaxInvoiceRules = [
        Fields::BUSINESS_GSTIN => 'sometimes|string|size:15',
        Fields::SUPPLY_TYPE    => 'required|string|size:10|custom',
        Fields::GST_AMOUNT     => 'required_with:' . Fields::SUPPLY_TYPE . '|integer|min:0',
        Fields::CESS_AMOUNT    => 'required_with:' . Fields::SUPPLY_TYPE . '|integer|min:0',
        Fields::INVOICE_NUMBER => 'sometimes|string',
        Fields::INVOICE_DATE   => 'sometimes|epoch',
        Fields::CUSTOMER_NAME  => 'sometimes|string',
    ];

    /**
     * @param $attribute
     * @param $value
     * Validates allowed types for order meta.
     * @throws BadRequestValidationFailureException
     */
    protected function validateType($attribute, $value)
    {
        if ((new Type)->isValidType($value) === false)
        {
            throw new BadRequestValidationFailureException(
                sprintf('%s is not a valid order_meta type', $value)
            );
        }
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateSupplyType($attribute, $value)
    {
        if (TaxInvoice\Type::isValidType($value) === false)
        {
            throw new BadRequestValidationFailureException(
                sprintf('%s is not a valid supply type', $value)
            );
        }
    }
}

