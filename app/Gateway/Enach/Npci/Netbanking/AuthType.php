<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use RZP\Models\Payment;

class AuthType
{
    const NETBANKING    = 'NetBanking';
    const DEBITCARD     = 'DebitCard';

    public static function getAuthType($input)
    {
        $authType = $input['payment']['auth_type'];

        if ($authType === Payment\AuthType::DEBITCARD)
        {
            $authType = AuthType::DEBITCARD;

            return $authType;
        }

        $authType = AuthType::NETBANKING;

        return $authType;
    }
}
