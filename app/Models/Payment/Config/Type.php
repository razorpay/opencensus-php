<?php

namespace RZP\Models\Payment\Config;

class Type
{
    const LATE_AUTH              = 'late_auth';
    const CHECKOUT               = 'checkout';
    const LOCALE                 = 'locale';
    const RISK                   = 'risk';
    const DCC                    = 'dcc';

    protected static $supportedConfigType = [
      self::LATE_AUTH, self::CHECKOUT, self::LOCALE, self::RISK, self::DCC,
    ];

    public function isConfigTypeSupported($type)
    {
        if (array_search($type, self::$supportedConfigType) === false)
        {
            return false;
        }

        return true;
    }
}
