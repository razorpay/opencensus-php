<?php

namespace RZP\Models\Merchant;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Card;
use RZP\Models\Payment\Analytics;

class AnalyticsValidator extends Base\Validator
{
    const ANALYTICS = 'analytics';

    protected $strict = false;

    const METHODS = ['netbanking', 'cards', 'wallets'];

    const NETWORK = ['visa', 'mastercard'];

    const DEVICES = ['desktop', 'mobile', 'tablet'];

    const BROWSER = ['chrome', 'IE', 'firefox'];

    const OS = ['windows', 'linux', 'macos'];

    const PLATFORM = ['browser', 'mobile-sdk'];

    protected static $analyticsRules = [

        Order\Entity::METHOD          => 'sometimes | custom',
        Card\Entity::NETWORK          => 'sometimes | custom',
        Analytics\Entity::DEVICE      => 'sometimes | custom',
        Analytics\Entity::BROWSER     => 'sometimes | custom',
        Analytics\Entity::OS          => 'sometimes | custom',
        Analytics\Entity::PLATFORM    => 'sometimes | custom',
    ];

    public function validateAnalyticsInputFilter($input)
    {
        $this->validateInput(self::ANALYTICS, $input);
    }

    public function validateMethod(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::METHODS, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid method',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::METHODS) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid method',
                    null,
                    $value);
            }
        }
    }

    public function validateNetwork(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::NETWORK, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid network',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::NETWORK) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid network',
                    null,
                    $value);
            }
        }
    }

    public function validateDevice(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::DEVICES, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid device',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::DEVICES) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid device',
                    null,
                    $value);
            }
        }
    }

    public function validateBrowser(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::BROWSER, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid browser',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::BROWSER) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid browser',
                    null,
                    $value);
            }
        }
    }

    public function validateOs(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::OS, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid os',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::OS) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid os',
                    null,
                    $value);
            }
        }
    }

    public function validatePlatform(string $attribute, $value)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder(self::PLATFORM, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid platform',
                    null,
                    $value);
            }
        }
        else
        {
            if (in_array($value, self::PLATFORM) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid platform',
                    null,
                    $value);
            }
        }
    }

    protected function isSubsetWithoutOrder(array $parentArray, array $childArray): bool
    {
        foreach ($childArray as $item)
        {
            if (in_array($item, $parentArray) === false)
            {
                return false;
            }
        }
        return true;
    }

}
