<?php

namespace RZP\Models\PayoutOutbox;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYOUT_DATA     => 'required|json|max:5000',
        Entity::REQUEST_TYPE    => 'required|string|max:255',
        Entity::SOURCE          => 'required|string|max:255',
        Entity::PRODUCT         => 'required|string|max:255',
        Entity::MERCHANT_ID     => 'required|string|size:14',
        Entity::USER_ID         => 'required|string|size:14',
        Entity::EXPIRES_AT      => 'required|epoch',
    ];

    // editRules will not be needed since update operation is not applicable for this entity. Confirm and remove this
    protected static $editRules = [
        Entity::PAYOUT_DATA     => 'required|json|max:5000',
        Entity::REQUEST_TYPE    => 'required|string|max:255',
        Entity::SOURCE          => 'required|string|max:255',
        Entity::PRODUCT         => 'required|string|max:255',
        Entity::MERCHANT_ID     => 'required|string|size:14',
        Entity::USER_ID         => 'required|string|size:14',
        Entity::EXPIRES_AT      => 'required|epoch',
    ];

    /**
     * validateType validate if the type of the request
     *
     * @param string $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateType(string $value)
    {
        if (RequestType::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Request type is invalid: ' . $value);
        }
    }

    /**
     * @param string $type
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateRequestType(string $type) {
        $this->validateType($type);
    }
}
