<?php

namespace RZP\Models\Base;

class Utility
{
    public static function isUpdatedAndroidSdk($input)
    {
        if ((isset($input['_'])) and
            (isset($input['_']['platform'])) and
            ($input['_']['platform'] === 'android') and
            (isset($input['_']['library'])) and
            ($input['_']['library'] === 'checkoutjs') and
            (isset($input['_']['version'])) and
            (version_compare($input['_']['version'], '1.0.0') >= 0))
        {
            return true;
        }

        return false;
    }

}
