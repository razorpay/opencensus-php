<?php

namespace RZP\Models\Order\OrderMeta\Order1cc;

use RZP\Base;
use RZP\Models\Address;

class Validator extends Base\Validator
{
    protected static $create1CCOrderRules = [
        Fields::LINE_ITEMS_TOTAL => 'required|integer',
        Fields::LINE_ITEMS       => 'sometimes|array|custom',
        Fields::PROMOTIONS       => 'sometimes|array|custom',
    ];

    protected static $edit1CCOrderRules = [
        Fields::LINE_ITEMS       => 'sometimes|custom',
        Fields::SHIPPING_FEE     => 'sometimes|integer',
        Fields::COD_FEE          => 'sometimes|integer',
        Fields::PROMOTIONS       => 'sometimes|array|custom',
        Fields::CUSTOMER_DETAILS => 'sometimes|array|custom',
    ];

    protected static $editCustomerDetailsRules = [
        Fields::CUSTOMER_DETAILS => 'required|array|custom',
    ];

    protected static $lineItemRules = [
        Fields::LINE_ITEM_TYPE                                                   => 'sometimes|string|max:32',
        Fields::LINE_ITEM_SKU                                                    => 'required|string|max:128',
        Fields::LINE_ITEM_VARIANT_ID                                             => 'sometimes|string|max:128',
        Fields::LINE_ITEM_OTHER_PRODUCT_CODES                                    => 'sometimes|array',
        Fields::LINE_ITEM_PRICE                                                  => 'required|integer',
        Fields::LINE_ITEM_OFFER_PRICE                                            => 'required|integer',
        Fields::LINE_ITEM_TAX_AMOUNT                                             => 'sometimes|integer',
        Fields::LINE_ITEM_QUANTITY                                               => 'required|integer',
        Fields::LINE_ITEM_NAME                                                   => 'required|string|max:128',
        Fields::LINE_ITEM_DESCRIPTION                                            => 'sometimes|string|max:256',
        Fields::LINE_ITEM_WEIGHT                                                 => 'sometimes|integer',
        Fields::LINE_ITEM_DIMENSIONS                                             => 'sometimes|array',
        Fields::LINE_ITEM_DIMENSIONS . "." . Fields::LINE_ITEM_DIMENSIONS_HEIGHT => 'required_with:' . Fields::LINE_ITEM_DIMENSIONS . '|integer',
        Fields::LINE_ITEM_DIMENSIONS . "." . Fields::LINE_ITEM_DIMENSIONS_WIDTH  => 'required_with:' . Fields::LINE_ITEM_DIMENSIONS . '|integer',
        Fields::LINE_ITEM_DIMENSIONS . "." . Fields::LINE_ITEM_DIMENSIONS_LENGTH => 'required_with:' . Fields::LINE_ITEM_DIMENSIONS . '|integer',
        Fields::LINE_ITEM_IMAGE_URL                                              => 'sometimes|url',
        Fields::LINE_ITEM_PRODUCT_URL                                            => 'sometimes|url',
        Fields::LINE_ITEM_NOTES                                                  => 'sometimes|array',
    ];

    protected static $promotionRules = [
        Fields::PROMOTIONS_REFERENCE_ID => 'required|string|max:128',
        Fields::PROMOTIONS_TYPE         => 'sometimes|string|max:128',
        Fields::PROMOTIONS_CODE         => 'required|string|max:16',
        Fields::PROMOTIONS_VALUE        => 'required|integer',
        Fields::PROMOTIONS_VALUE_TYPE   => 'sometimes|string|max:16',
        Fields::PROMOTIONS_DESCRIPTION  => 'sometimes|string|max:512',
    ];

    protected static $customerDetailsRules = [
        Fields::CUSTOMER_DETAILS_ID               => 'sometimes|string|max:128',
        Fields::CUSTOMER_DETAILS_NAME             => 'sometimes|string|max:64',
        Fields::CUSTOMER_DETAILS_CONTACT          => 'required|contact_syntax',
        Fields::CUSTOMER_DETAILS_EMAIL            => 'sometimes|email',
        Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS => 'sometimes|array|custom',
        Fields::CUSTOMER_DETAILS_BILLING_ADDRESS  => 'sometimes|array|custom',
    ];

    protected function validateShippingAddress($attribute, $value)
    {
        (new Address\Validator)->validateInput('createFor1ccOrder', $value);
    }

    protected function validateBillingAddress($attribute, $value)
    {
        (new Address\Validator)->validateInput('createFor1ccOrder', $value);
    }

    protected function validateLineItems($attribute, $value)
    {
        foreach ($value as $item)
        {
            $this->validateInput('lineItem', $item);
        }
    }

    protected function validatePromotions($attribute, $value)
    {
        foreach ($value as $promo)
        {
            $this->validateInput('promotion', $promo);
        }
    }

    protected function validateCustomerDetails($attribute, $value)
    {
        $this->validateInput('customerDetails', $value);
    }
}
