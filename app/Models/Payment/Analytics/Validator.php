<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::PAYMENT_ID              => 'required|alpha_num|size:14',
        Entity::TERMINAL_ID             => 'required|alpha_num|size:14',
        Entity::TERMINAL_STATUS         => 'sometimes|boolean',
        Entity::TERMINAL_RESPONSE_TIME  => 'sometimes|numeric',
        Entity::TERMINAL_STATUS_CODE    => 'sometimes|integer',
        Entity::TERMINAL_STATUS_MSG     => 'sometimes|string',
        Entity::PAYMENT_TYPE            => 'sometimes|integer|in:0,1',
        Entity::CHECKOUT_ID             => 'sometimes|alpha_num|size:14',
        Entity::ATTEMPTS                => 'sometimes|integer|min:0',
        Entity::LIBRARY                 => 'sometimes',
        Entity::PLATFORM                => 'sometimes',
        Entity::BROWSER                 => 'sometimes',
        Entity::OS                      => 'sometimes',
        Entity::DEVICE                  => 'sometimes',
        Entity::REFERER                 => 'sometimes|url',
        Entity::USER_AGENT              => 'sometimes|string',
        Entity::IP                      => 'sometimes|ip',
     );

    protected static $createValidators = array(
        'checkout_id',
        'library',
        'platform',
        'browser',
        'os',
        'device',
    );

    protected function validateDevice($metadata)
    {
        if (isset($metadata[Entity::DEVICE]))
        {
            if (Metadata::validateDevice($metadata[Entity::DEVICE]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_DEVICE);
            }

            return true;
        }

        return true;
    }

    protected function validateOs($metadata)
    {
        if (isset($metadata[Entity::OS]))
        {
            if (Metadata::validateOs($metadata[Entity::OS]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_OS, ['$metadata' => $metadata]);
            }

            return true;
        }

        return true;
    }

    protected function validateBrowser($metadata)
    {
        if (isset($metadata[Entity::BROWSER]))
        {
            if (Metadata::validateBrowser($metadata[Entity::BROWSER]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_BROWSER);
            }

            return true;
        }

        return true;
    }

    protected function validateLibrary($metadata)
    {
        if (isset($metadata[Entity::LIBRARY]))
        {
            if (Metadata::validateLibrary($metadata[Entity::LIBRARY]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_LIBRARY);
            }

            return true;
        }

        return true;
    }

    protected function validatePlatform($metadata)
    {
        if (isset($metadata[Entity::PLATFORM]))
        {
            if (Metadata::validatePlatform($metadata[Entity::PLATFORM]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_PLATFORM);
            }

            return true;
        }

        return true;
    }

    protected function validateCheckoutId($metadata)
    {
        if (isset($metadata[Entity::CHECKOUT_ID]))
        {
            if (Entity::validateCheckDigit($metadata[Entity::CHECKOUT_ID]) !== true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CHECKOUT_ID);
            }

            return true;
        }

        return true;
    }
}