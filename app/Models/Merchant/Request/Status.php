<?php

namespace RZP\Models\Merchant\Request;

class Status
{
    /*
     * Enum values used for request status
     */
    const UNDER_REVIEW        = 'under_review';
    const NEEDS_CLARIFICATION = 'needs_clarification';
    const ACTIVATED           = 'activated';
    const REJECTED            = 'rejected';
    const ACTIVATED_MCC_PENDING = 'activated_mcc_pending';

    /*
     * Allowed next statuses mapping
     */
    const ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING = [
        self::UNDER_REVIEW        => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED],
        self::NEEDS_CLARIFICATION => [self::UNDER_REVIEW],
        self::REJECTED            => [self::UNDER_REVIEW],
        self::ACTIVATED_MCC_PENDING => [self::NEEDS_CLARIFICATION, self::ACTIVATED],
        self::ACTIVATED           => [],
    ];
}
