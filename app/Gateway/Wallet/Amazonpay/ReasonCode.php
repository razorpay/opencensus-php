<?php

namespace RZP\Gateway\Wallet\Amazonpay;

final class ReasonCode
{
    const SUCCESS = '001';

    const ORDER_REFERENCE_SUCCESS = 'UpfrontChargeSuccess';

    public static function getVerifyReasonCodeMappedToAuthReasonCode(string $verifyReasonCode)
    {
        if ($verifyReasonCode === self::ORDER_REFERENCE_SUCCESS)
        {
            return self::SUCCESS;
        }

        // Random reason code created to show that verify response was a failure
        return '-100';
    }
}
