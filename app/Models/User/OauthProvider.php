<?php

namespace RZP\Models\User;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class OauthProvider
{
    const GOOGLE   = 'google';

    public static function exists(string $oauthProvider): bool
    {
        $key = __CLASS__ . '::' . strtoupper($oauthProvider);

        return ((defined($key) === true) and (constant($key) === $oauthProvider));
    }

    public static function validate(string $provider)
    {
        if (self::exists($provider) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_OAUTH_PROVIDER_INVALID,
                Entity::OAUTH_PROVIDER,
                [Entity::OAUTH_PROVIDER => $provider]);
        }
    }
}
