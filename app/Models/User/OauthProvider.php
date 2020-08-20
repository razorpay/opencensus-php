<?php

namespace RZP\Models\User;

class OauthProvider
{
    const GOOGLE   = 'google';

    public static function exists(string $oauthProvider): bool
    {
        $key = __CLASS__ . '::' . strtoupper($oauthProvider);

        return ((defined($key) === true) and (constant($key) === $oauthProvider));
    }
}
