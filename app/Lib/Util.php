<?php

namespace App\Lib;

use App\Http\ApiUrl;

class Util
{
    public static function random_alpha_string($length = 1)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($chars), 0, $length);
    }

    public static function array_recursive_diff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue)
        {
            if (is_array($aArray2) === true and array_key_exists($mKey, $aArray2) === true)
            {
                if (is_array($mValue))
                {
                    $aRecursiveDiff = Util::array_recursive_diff($mValue, $aArray2[$mKey]);

                    if (count($aRecursiveDiff))
                    {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                }
                else
                {
                    if ($mValue != $aArray2[$mKey])
                    {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            }
            else
            {
                $aReturn[$mKey] = $mValue;
            }
        }

        return $aReturn;
    }

    public function debugLogsEnable()
    {
        $baseUrl = ApiUrl::getApiBaseUrl();

        $env = \App::environment();

        $allowedHosts = [
            'dev'        => '*',
            'dev_docker' => '*',
            'beta'       => [
                'https://beta-api.razorpay.in/v1/',
                'https://beta-api.stage.razorpay.in/v1/',
            ],
            'production' => [
                'https://api-dark.razorpay.com/v1/',
            ],
        ];

        $allowedHostsEnv = $allowedHosts[$env] ?? [];

        return (($allowedHostsEnv === '*') or
            (in_array($baseUrl, $allowedHostsEnv, true) === true));
    }
}
