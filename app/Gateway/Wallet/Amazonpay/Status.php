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
    const PENDING                 = 'Pending';
    const COMPLETED               = 'Completed';
    const DECLINED                = 'Declined';

    public static function getVerifyReasonCodeMappedToAuthStatus(string $reasonCode)
    {
        if ($reasonCode === ReasonCode::ORDER_REFERENCE_SUCCESS)
        {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
