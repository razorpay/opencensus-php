<?php

namespace App\Lib;

use App\Http\ApiUrl;

class Util
{
    public static function random_alpha_string($length = 1): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($chars), 0, $length);
    }

    public static function array_recursive_diff($aArray1, $aArray2): array
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

    public function debugLogsEnable(): bool
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
                'https://api-dark-concierge.razorpay.com/v1/',
            ],
        ];

        $allowedHostsEnv = $allowedHosts[$env] ?? [];

        return (($allowedHostsEnv === '*') or
            (in_array($baseUrl, $allowedHostsEnv, true) === true));
    }

    public static function mask_phone($phone = null)
    {
        if ((is_string($phone) === false) or (empty($phone) === true))
        {
            return null;
        }

        $phoneLen = strlen($phone);

        return substr($phone, 0, 2) .
            str_repeat('*', $phoneLen - 4) .
            substr($phone, $phoneLen - 2, 2);
    }

    public static function mask_email($email = null, float $percentageToMask = 0.7)
     {
         $trace = \App::getFacadeRoot()['trace'];

         $maskedEmail = null;

         if ((is_string($email) === false) or (empty($email) === true))
         {
             return null;
         }
         try
         {
             $email = explode('@', $email); // ex: test_email@gmail.com

             $emailName = $email[0]; // test_email

             $emailDomain = $email[1]; // gmail.com

             $emailDomain = explode('.', $emailDomain);

             $domain = $emailDomain[0]; // gmail

             $topLevelDomain = $emailDomain[1]; // .com

             $emailLen = strlen($emailName);

             $lengthToMask = ceil($emailLen * $percentageToMask);

             // replace the name except first 3 characters with *
             $maskedEmailName = substr($emailName, 0, $emailLen - $lengthToMask) .
                 str_repeat('*', $lengthToMask);

             // replace the domain with *, except the first and the last character
             $maskedDomain = $domain[0] .
                 str_repeat('*', strlen($domain) - 2) .
                 $domain[strlen($domain) - 1];

             $maskedEmail = sprintf('%s@%s.%s', $maskedEmailName, $maskedDomain, $topLevelDomain);
         }
         catch (\Exception $e)
         {
             $trace->error('INVALID_EMAIL_CANNOT_MASK', [
                 'email' => $email
             ]);
         }

         return $maskedEmail;
     }

    public static function maskLoginSignupInput($input)
    {
        $copiedInput =  $input;

        if(isset($copiedInput['email']))
        {
            $copiedInput['email'] = Util::mask_email($copiedInput['email']);
        }

        if(isset($copiedInput['contact_mobile']))
        {
            $copiedInput['contact_mobile'] = Util::mask_phone($copiedInput['contact_mobile']);
        }

        if(isset($copiedInput['password'])) unset($copiedInput['password']);

        if(isset($copiedInput['password_confirmation'])) unset($copiedInput['password_confirmation']);

        if(isset($copiedInput['otp'])) unset($copiedInput['otp']);

        if(isset($copiedInput['otp_auth_token'])) unset($copiedInput['otp_auth_token']);

        if(isset($copiedInput['token'])) unset($copiedInput['token']);

        if(isset($copiedInput['id_token'])) unset($copiedInput['id_token']);

        return $copiedInput;
    }

    public static function arraySome(array $data, callable $callback)
    {
        $result = array_filter($data, $callback);

        return count($result) > 0;
    }

    public static function getIsMerchantLoginCacheKey($merchantId): string
    {
        return 'is_merchant_login_'.$merchantId;
    }
}
