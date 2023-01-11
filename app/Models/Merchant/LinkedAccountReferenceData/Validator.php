<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Base;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BUSINESS_NAME              => 'required|string|min:3|max:255',
        Entity::BUSINESS_TYPE              => 'required|string|min:3|max:255',
        Entity::BENEFICIARY_NAME           => 'required|string|min:3|max:120',
        Entity::ACCOUNT_NAME               => 'required|string|max:255',
        Entity::ACCOUNT_EMAIL              => 'required|string|max:255',
        Entity::ACCOUNT_NUMBER             => 'required|string|max:255',
        Entity::DASHBOARD_ACCESS           => 'sometimes|boolean|in:0,1',
        Entity::CUSTOMER_REFUND_ACCESS     => 'sometimes|boolean|in:0,1',
        Entity::IFSC_CODE                  => 'required|alpha_num|size:11',
        Entity::CATEGORY                   => 'required|string|max:255|custom',
    ];

    protected static $editRules = [
        Entity::BUSINESS_NAME              => 'sometimes|string|min:3|max:255',
        Entity::BUSINESS_TYPE              => 'sometimes|string|min:3|max:255',
        Entity::BENEFICIARY_NAME           => 'sometimes|string|min:3|max:120',
        Entity::ACCOUNT_NAME               => 'sometimes|string|max:255',
        Entity::ACCOUNT_EMAIL              => 'sometimes|string|max:255',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:255',
        Entity::DASHBOARD_ACCESS           => 'sometimes|boolean|in:0,1',
        Entity::CUSTOMER_REFUND_ACCESS     => 'sometimes|boolean|in:0,1',
        Entity::IFSC_CODE                  => 'sometimes|alpha_num|size:11',
        Entity::CATEGORY                   => 'sometimes|string|max:255|custom',
    ];

    protected static $createManyRules = [
        Entity::LA_REFERENCE_DATA           => 'required|sequential_array|min:1'
    ];

    public function validateCategory(string $attribute, string $value)
    {
        if(Category::exists($value) === false)
        {
            throw new BadRequestException("{$value} Not a valid category");
        }
    }
}
