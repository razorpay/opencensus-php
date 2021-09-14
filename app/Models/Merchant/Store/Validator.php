<?php


namespace RZP\Models\Merchant\Store;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $updateRules = [
        Constants::NAMESPACE            => 'required|string|custom',

        ConfigKey::MTU_COUPON_POPUP_COUNT   => 'filled|integer|min:1|max:5',
    ];

    protected static $fetchRules = [
        Constants::NAMESPACE            => 'filled|string|custom',
    ];

    public function validateNamespace($attirbute, $value)
    {
        if(array_key_exists($value, ConfigKey::NAMESPACE_KEY_CONFIG) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid namespace: '. $value);
        }
    }
}
