<?php

namespace Models\Key;

use Models\Base;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Key;

class Validator extends Base\Validator
{
    protected static $demoKeys = array(
        '1DP5mmOlF5G5ag',
        '0wFRWIZnH65uny');

    /**
     * This validator is used before operations on key
     * to verify it's not one of the demo keys on
     * which operations are not permitted.`
     * @param  string $keyId
     */
    public static function checkForDemoKeys($keyId)
    {
        if (in_array($keyId, static::$demoKeys))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT);
        }
    }
}