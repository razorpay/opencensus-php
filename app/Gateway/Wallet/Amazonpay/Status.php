<?php

namespace RZP\Gateway\Wallet\Amazonpay;

final class Status
{
    /**
     * Verify specific statuses
     */
    const SUCCESS                 = 'SUCCESS';
    const FAILURE                 = 'FAILURE';

    /**
     * Refund specific statuses
     */
    const PENDING                 = 'pending';
    const COMPLETED               = 'completed';
    const DECLINED                = 'declined';

    public static function getVerifyReasonCodeMappedToAuthStatus(string $reasonCode)
    {
        if ($reasonCode === ReasonCode::ORDER_REFERENCE_SUCCESS)
        {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * Since amazon returns status
     *
     * @param string $expected
     * @param string $actual
     * @return bool
     */
    public static function matches(string $expected, string $actual): bool
    {
        return (strcasecmp($expected, $actual) === 0);
    }
}
