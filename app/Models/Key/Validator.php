<?php

namespace RZP\Models\Key;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $demoKeys = array(
        '1DP5mmOlF5G5ag',
        '0wFRWIZnH65uny',
        'OS3GZfI9mcqKbc'  // Prod - X Demo User
    );

    protected static $verifyOtpRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::TOKEN           => 'required|unsigned_id'
    ];

    protected static $bulkRegenerateApiKeyRules = [
        Constants::MERCHANT_IDS => 'required|array|min:1',
        Constants::REASON       => 'required|string'
    ];

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

    /**
     * This function is used to check if a merchant has key access in LIVE mode.
     * @param Merchant\Entity $merchant
     * @param string $mode
     */
    public function checkHasKeyAccess(Merchant\Entity $merchant, string $mode)
    {
        if (($mode === Mode::LIVE) and
            ($merchant->getHasKeyAccess() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS);
        }
    }
}
