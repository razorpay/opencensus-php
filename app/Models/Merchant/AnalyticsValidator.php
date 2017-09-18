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

    protected static $method = ['netbanking', 'cards', 'wallets'];

    protected static $network = ['visa', 'mastercard'];

    protected static $device = ['desktop', 'mobile', 'tablet'];

    protected static $browser = ['chrome', 'IE', 'firefox'];

    protected static $os = ['windows', 'linux', 'macos'];

    protected static $platform = ['browser', 'mobile-sdk'];

    protected static $analyticsRules = [
        Order\Entity::METHOD          => 'sometimes|custom',
        Card\Entity::NETWORK          => 'sometimes|custom',
        Analytics\Entity::DEVICE      => 'sometimes|custom',
        Analytics\Entity::BROWSER     => 'sometimes|custom',
        Analytics\Entity::OS          => 'sometimes|custom',
        Analytics\Entity::PLATFORM    => 'sometimes|custom',
    ];

    public function validateAnalyticsInputFilter($input)
    {
        $this->validateInput(self::ANALYTICS, $input);
    }

    protected function callCustomRuleValidatorFunction($func, $attribute, $value, $parameters)
    {
        if (function_exists($func) === true)
        {
            parent::callCustomRuleValidatorFunction($func, $attribute, $value, $parameters);

            return;
        }

        $parentArrayVar = $attribute;

        $this->validateFilter($attribute, $value, self::$$parentArrayVar);
    }

    protected function validateFilter(string $attribute, $value, array $parentArray)
    {
        if (is_array($value) === true)
        {
            if ($this->isValuesSubsetOfParent($parentArray, $value) === false)
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

    protected function isValuesSubsetOfParent(array $parentArray, array $values): bool
    {
        return (count(array_values(array_diff($values, $parentArray))) === 0);
    }

}
