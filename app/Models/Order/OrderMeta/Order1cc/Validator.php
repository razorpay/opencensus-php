<?php

namespace RZP\Models\Order\OrderMeta\Order1cc;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Address;
use RZP\Trace\TraceCode;
use RZP\Models\Order\Entity;

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
        Fields::COD_INTELLIGENCE => 'sometimes|array',
        Fields::REVIEWED_AT      => 'sometimes|integer',
        Fields::REVIEWED_BY      => 'sometimes|email',
        Fields::REVIEW_STATUS    => 'sometimes|in:approved,canceled,hold,approval_initiated,hold_initiated,cancel_initiated',
        Fields::SHIPPING_METHOD  => 'sometimes|array|custom',
        Fields::UTM_PARAMETERS   => 'sometimes|array|custom',
    ];

    protected static $editCustomerDetailsRules = [
        Fields::CUSTOMER_DETAILS => 'required|array|custom',
        Fields::SHIPPING_METHOD  => 'sometimes|array|custom',
    ];

    protected static $shippingMethodRules = [
        Fields::ID                 => 'sometimes|string|max:64',
        Fields::NAME               => 'required|string|max:156',
        Fields::DESCRIPTION        => 'sometimes|string|max:256',
        Fields::SHIPPING_FEE       => 'sometimes|integer',
        Fields::COD_FEE            => 'sometimes|integer',
    ];

    protected static $editOrderNotesRules = [
        Fields::GSTIN                 => 'sometimes|string|size:15',
        Fields::ORDER_INSTRUCTIONS    => 'sometimes|string|max:256'
    ];

    protected static $lineItemRules = [
        Fields::LINE_ITEM_TYPE                                                   => 'sometimes|string|max:128',
        Fields::LINE_ITEM_SKU                                                    => 'sometimes|string|max:128',
        Fields::LINE_ITEM_VARIANT_ID                                             => 'sometimes|string|max:128',
        Fields::LINE_ITEM_PRODUCT_ID                                             => 'sometimes|string|max:128',
        Fields::LINE_ITEM_OTHER_PRODUCT_CODES                                    => 'sometimes|array',
        Fields::LINE_ITEM_PRICE                                                  => 'required|integer',
        Fields::LINE_ITEM_OFFER_PRICE                                            => 'sometimes|integer',
        Fields::LINE_ITEM_TAX_AMOUNT                                             => 'sometimes|integer',
        Fields::LINE_ITEM_QUANTITY                                               => 'required|integer',
        Fields::LINE_ITEM_NAME                                                   => 'required|string|max:128',
        Fields::LINE_ITEM_VARIANT_NAME                                           => 'sometimes|string|max:128',
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
        Fields::PROMOTIONS_REFERENCE_ID => 'required|string|max:512',
        Fields::PROMOTIONS_TYPE         => 'sometimes|string|max:128',
        Fields::PROMOTIONS_CODE         => 'required|string|max:512',
        Fields::PROMOTIONS_VALUE        => 'required|integer',
        Fields::PROMOTIONS_VALUE_TYPE   => 'sometimes|string|max:128',
        Fields::PROMOTIONS_DESCRIPTION  => 'sometimes|string|max:512',
    ];


    protected static $customerDetailsRules = [
        Fields::CUSTOMER_DETAILS_ID               => 'sometimes|string|max:128',
        Fields::CUSTOMER_DETAILS_NAME             => 'sometimes|string|max:64',
        Fields::CUSTOMER_DETAILS_CONTACT          => 'sometimes|contact_syntax',
        Fields::CUSTOMER_DETAILS_EMAIL            => 'sometimes|email',
        Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS => 'sometimes|array|custom',
        Fields::CUSTOMER_DETAILS_BILLING_ADDRESS  => 'sometimes|array|custom',
        Fields::CUSTOMER_DETAILS_DEVICE           => 'sometimes|array|custom',
    ];

    protected static $customerDeviceDetailsRules = [
        Fields::CUSTOMER_DETAILS_DEVICE_ID          => 'required|regex:/^\d{1}\.[a-zA-Z0-9]{40}\.\d{13}\.\d{8}$/',
    ];

    protected static $getCODOrderRules = [
        Fields::COD_ELIGIBILITY_RISK_TIER           => 'sometimes|in:low,medium,high',
        Fields::REVIEW_STATUS                       => 'sometimes|array|max:6',
        Fields::REVIEW_STATUS."*"                   => 'distinct|in:approved,canceled,hold,approval_initiated,hold_initiated,cancel_initiated,null',
        Entity::RECEIPT                             => 'sometimes|string|max:40',
        Entity::ID                                  => 'sometimes|string|size:20',
        Entity::FROM                                => 'integer',
        Entity::TO                                  => 'integer',
        Entity::COUNT                               => 'integer|min:1|max:50',
        Entity::SKIP                                => 'integer',
        Fields::REVIEW_MODE => 'sometimes|in:automation,manual',
    ];

    protected static $getPrepayOrdersRules = [
        Fields::COD_ELIGIBILITY_RISK_TIER           => 'sometimes|in:low,medium,high',
        Entity::RECEIPT                             => 'sometimes|string|max:40',
        Entity::ID                                  => 'sometimes|string|size:20',
        Entity::FROM                                => 'integer',
        Entity::TO                                  => 'integer',
        Entity::COUNT                               => 'integer|min:1|max:50',
        Entity::SKIP                                => 'integer',
        Fields::MAGIC_PAYMENT_LINK_STATUS_KEY       => 'sometimes|in:awaited,sent,failed,expired,cancelled,paid'
    ];

    protected static $getPrepayOrderRules = [
        Entity::ID                                  => 'required|string|size:20',
    ];

    protected static $actionRules = [
        Constants::ACTION       => 'required|in:approve,cancel,hold',
        Entity::ID              => 'required|array|min:1',
        Entity::ID."*"          => 'distinct|string|size:20'
    ];

    protected static $reviewStatusRules = [
        Fields::REVIEW_STATUS       => 'required|in:approved,canceled,hold,approval_initiated,hold_initiated,cancel_initiated',
        Entity::ID                  => 'required|string|size:20',
    ];

    protected static $utmParametersRules = [
        Fields::UTM_SOURCE           => 'sometimes|string|max:512',
        Fields::UTM_MEDIUM           => 'sometimes|string|max:512',
        Fields::UTM_CAMPAIGN         => 'sometimes|string|max:512',
        Fields::UTM_TERM             => 'sometimes|string|max:512',
        Fields::UTM_CONTENT          => 'sometimes|string|max:512',
        Fields::GCLID                => 'sometimes|string',
        Fields::FBCLID               => 'sometimes|string',
        Fields::REF                  => 'sometimes|string',
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

    protected function validateUtmParameters($attribute, $value)
    {
        $this->validateInput('utmParameters', $value);
    }

    protected function validatePromotions($attribute, $value)
    {
        foreach ($value as $promo)
        {
            $this->validateInput('promotion', $promo);
        }
    }

    public function validateDevice($attribute, $value)
    {
        if (empty($value['id']) === true)
        {
            return new Exception\BadRequestValidationFailureException('The id field is required.');
        }

        $device['id'] = $value['id'];

        $this->validateInput("customerDeviceDetails", $device);

        if(!isset($value['ip']))
        {
            $this->getTrace()->error(TraceCode::RTO_1CC_DEVICE_IP_NOT_FOUND, $value);
        }

        if(!isset($value['user_agent']))
        {
            $this->getTrace()->error(TraceCode::RTO_1CC_DEVICE_USER_AGENT_NOT_FOUND, $value);
        }
    }

    protected function validateCustomerDetails($attribute, $value)
    {
        $this->validateInput('customerDetails', $value);
    }

    protected function validateEditOrderNotes(string $attribute, array $value)
    {
        $this->validateInput('editOrderNotes', $value);
    }

    protected function validateShippingMethod(string $attribute, array $value)
    {
        $this->validateInput('shippingMethod', $value);
    }
}
