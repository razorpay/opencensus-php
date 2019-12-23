<?php

namespace RZP\Gateway\Mozart\GetSimpl;

class Helper
{
    public static function getPaymentInputParameters($input, $payment)
    {
        $input['contact'] = $payment['contact'];
        $input['email']   = $payment['email'];
        $input['method']  = $payment['method'];
        $input['provider']= $payment['wallet'];
        $input['amount']  = $payment['amount'];
        $input['currency']= $payment['currency'];
        $input['ott']     = Constants::GETSIMPLTOKEN;

        return $input;
    }
}
