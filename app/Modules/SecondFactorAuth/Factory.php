<?php

namespace RZP\Modules\SecondFactorAuth;

class Factory
{
    /**
     * Makes the class (type of BaseAuth) on the basis
     * of authType passed
     *
     * @param  string $authType
     * @return BaseAuth
     */
    public static function make(string $authType): BaseAuth
    {
        switch($authType)
        {
            case 'SmsOtpAuth':
                return new SmsOtpAuth;

            default:
                return new SmsOtpAuth;
        }
    }
}
