<?php

namespace RZP\Models\Customer\AppToken;

use Session;

class SessionHelper
{
    public static function getAppTokenFromSession($mode)
    {
        // Check if app token is present in session
        $key = $mode . '_app_token';

        $appToken = Session::get($key);

        return $appToken;
    }

    public static function removeAppTokenFromSession($mode)
    {
        $key = $mode . '_app_token';

        $appToken = Session::remove($key);

        return;
    }
}
