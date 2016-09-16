<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics\Metadata;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::PAYMENT_ID              => 'required|alpha_num|size:14',
        Entity::TERMINAL_ID             => 'required|alpha_num|size:14',
        Entity::CHECKOUT_ID             => 'sometimes|alpha_num|size:14',
        Entity::ATTEMPTS                => 'sometimes|integer|min:0',
        Entity::LIBRARY                 => 'sometimes',
        Entity::LIBRARY_VERSION         => 'sometimes',
        Entity::PLATFORM                => 'sometimes',
        Entity::PLATFORM_VERSION        => 'sometimes',
        Entity::BROWSER                 => 'sometimes',
        Entity::OS                      => 'sometimes',
        Entity::OS_VERSION              => 'sometimes',
        Entity::DEVICE                  => 'sometimes',
        Entity::REFERER                 => 'sometimes|url',
        Entity::USER_AGENT              => 'sometimes|string',
        Entity::IP                      => 'sometimes|ip',
        Entity::INTEGRATION             => 'sometimes',
        Entity::INTEGRATION_VERSION     => 'sometimes'
     );

    protected static $createValidators = array(
        Entity::CHECKOUT_ID,
        Entity::LIBRARY,
        Entity::PLATFORM,
        Entity::BROWSER,
        Entity::OS,
        Entity::DEVICE,
        Entity::INTEGRATION,
    );

    protected function validateCheckoutId($input)
    {
        if (empty($input[Entity::CHECKOUT_ID]))
        {
            return;
        }

        if (Entity::isValidBase62Id($input[Entity::CHECKOUT_ID]) !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CHECKOUT_ID);
        }
    }

    protected function validateLibrary($input)
    {
        if (empty($input[Entity::LIBRARY]))
        {
            return;
        }

        Metadata::validateLibrary($input[Entity::LIBRARY]);
    }

    protected function validatePlatform($input)
    {
        if (empty($input[Entity::PLATFORM]))
        {
            return;
        }

        Metadata::validatePlatform($input[Entity::PLATFORM]);
    }

    protected function validateBrowser($input)
    {
        if (empty($input[Entity::BROWSER]))
        {
            return;
        }

        Metadata::validateBrowser($input[Entity::BROWSER]);
    }

    protected function validateOs($input)
    {
        if (empty($input[Entity::OS]))
        {
            return;
        }

        Metadata::validateOs($input[Entity::OS]);
    }

    protected function validateDevice($input)
    {
        if (empty($input[Entity::DEVICE]))
        {
            return;
        }

        Metadata::validateDevice($input[Entity::DEVICE]);
    }

    protected function validateIntegration($input)
    {
        if (empty($input[Entity::INTEGRATION]))
        {
            return;
        }

        Metadata::validateIntegration($input[Entity::INTEGRATION]);
    }
}