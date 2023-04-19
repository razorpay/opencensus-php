<?php

namespace RZP\Models\NetbankingConfig;

use RZP\Base;
use RZP\Exception;


class Validator extends Base\Validator
{
    public function validateCreateConfig($input)
    {
        if (isset($input[Constants::MERCHANT_ID]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant id is mandatory parameter');
        }

        if (isset($input[Constants::FIELDS]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'fields is mandatory parameter');
        }

        $fields = $input[Constants::FIELDS];

        // check if key is valid
        foreach ($fields as $key => $value)
        {
            if(in_array($key, Constants::NETBANKING_CONFIGS) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'Parameter ' . $key . $value. ' is invalid');
            }
        }
    }

    public function validateFetchConfig($input)
    {
        if (isset($input[Constants::MERCHANT_ID]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant id is mandatory parameter');
        }
    }

    public function validateEditConfig($input)
    {
        if (isset($input[Constants::MERCHANT_ID]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant id is mandatory parameter');
        }

        if (isset($input[Constants::FIELDS]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'fields is mandatory parameter');
        }

        $fields = $input[Constants::FIELDS];

        // check if key is valid
        foreach ($fields as $key => $value)
        {
            if(in_array($key, Constants::NETBANKING_CONFIGS) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'Parameter ' . $key . $value. ' is invalid');
            }
        }
    }

}
