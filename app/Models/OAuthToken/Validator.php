<?php

namespace RZP\Models\OAuthToken;

use RZP\Base;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\User as User;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers\OauthMigration as H;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Header::MERCHANT_ID    => 'required|string|size:14',
        H::CLIENT_ID           => 'required|string|size:14',
        H::USER_ID             => 'required|string|size:14',
        H::REDIRECT_URI        => 'required|url',
    ];

    protected static $createForAppleWatchRules = [
        User\Entity::OTP    => 'required|string',
        User\Entity::TOKEN  => 'required|string'
    ];

    /**
     * @throws BadRequestException
     */
    public function validateCreateForAppleWatch(array $input, string $userId, string $merchantId, bool $merchantActivated, string $mode)
    {
        (new Validator())->validateInput('createForAppleWatch',$input);

        if ($mode !== Mode::LIVE)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                'mode',
                $mode,
                'Token request for Apple Watch is only supported in Live Mode'
            );
        }

        if (!$merchantActivated)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST);
        }

        (new \RZP\Models\Merchant\Validator())->validateUserIsOwnerForMerchant($userId, $merchantId);

    }
}
