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

    const METHOD = ['netbanking', 'cards', 'wallets'];

    const NETWORK = ['visa', 'mastercard'];

    const DEVICE = ['desktop', 'mobile', 'tablet'];

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
        $this->validateFilter($attribute, $value, self::METHOD);
    }

    public function validateNetwork(string $attribute, $value)
    {
        $this->validateFilter($attribute, $value, self::NETWORK);
    }

    public function validateDevice(string $attribute, $value)
    {
        $this->validateFilter($attribute, $value, self::DEVICE);
    }

    public function validateBrowser(string $attribute, $value)
    {
        $this->validateFilter($attribute, $value, self::BROWSER);
    }

    public function validateOs(string $attribute, $value)
    {
        $this->validateFilter($attribute, $value, self::OS);
    }

    public function validatePlatform(string $attribute, $value)
    {
        $this->validateFilter($attribute, $value, self::PLATFORM);
    }

    protected function validateFilter(string $attribute, $value, array $parentArray)
    {
        if (is_array($value) === true)
        {
            if ($this->isSubsetWithoutOrder($parentArray, $value) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid '.$attribute,
                    $attribute,
                    $value);
            }
        }
        else
        {
            if (in_array($value, $parentArray) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid '.$attribute,
                    $attribute,
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
