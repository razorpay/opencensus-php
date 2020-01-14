<?php

namespace RZP\Models\PayoutLink;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Utility
{

    const PERCENTAGE_TO_MASK = 0.7; // 70 %

    /**
     * Masks the customer email as follows
     * Input: test_email@gmail.com
     * Output: tes*****l@g****.com
     *
     * @param string $email
     * @return mixed|string
     */
    public static function getMaskedEmail(string $email)
    {
        $trace = App::getFacadeRoot()['trace'];

        $maskedEmail = $email;

        if (empty($email) === true)
        {
            return null;
        }
        try
        {
            // assuming that if this is filled, then its a valid email
            $email = explode('@', $email); // ex: test_email@gmail.com

            $emailName = $email[0]; // test_email

            $emailDomain = $email[1]; // gmail.com

            $emailDomain = explode('.', $emailDomain);

            $domain = $emailDomain[0]; // gmail

            $topLevelDomain = $emailDomain[1]; // .com

            $emailLen = strlen($emailName);

            $lengthToMask = ceil($emailLen * self::PERCENTAGE_TO_MASK);

            // replace the name except first 3 characters with *
            $maskedEmailName = substr($emailName, 0, $emailLen - $lengthToMask) .
                               str_repeat('*', $lengthToMask);

            // replace the domain with *, except the first and the last character
            $maskedDomain = $domain[0] .
                            str_repeat('*', strlen($domain) - 2) .
                            $domain[strlen($domain) - 1];

            $maskedEmail = sprintf('%s@%s.%s', $maskedEmailName, $maskedDomain, $topLevelDomain);
        } catch (\Exception $e)
        {
            // Do not want the page load to fail because the email was incorrect
            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::INVALID_EMAIL_CANNOT_MASK,
                                   [
                                       'email' => $email
                                   ]
            );
        }

        return $maskedEmail;
    }

    public static function getMaskedPhone(string $phone)
    {
        if (empty($phone) === true)
        {
            return null;
        }
        $phoneLen = strlen($phone);

        return substr($phone, 0, 2) .
               str_repeat('*', $phoneLen - 4) .
               substr($phone, $phoneLen - 2, 2);
    }
}
