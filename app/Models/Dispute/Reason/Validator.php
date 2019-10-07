<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CODE                => 'required|string|max:255',
        Entity::NETWORK             => 'required|custom',
        Entity::DESCRIPTION         => 'required|string|max:255',
        Entity::GATEWAY_CODE        => 'required|string|max:50',
        Entity::GATEWAY_DESCRIPTION => 'required|string|max:255',
    ];

    public function validateNetwork(string $attribute, string $value)
    {
        if ((Network::exists($value) === false) ||
            (Network::isValid($value) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Network is invalid: ' . $value);
        }
    }

    /**
     * Validates if given network value is defined without case sensitivity and returns network DB format
     *
     * @param string $value
     * @return string
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAndFetchFormattedNetwork(string $value) : string
    {
        if (Network::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Network is invalid: ' . $value);
        }

        return Network::getNetwork($value);
    }
}
