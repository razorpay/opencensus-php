<?php

namespace RZP\Models\Merchant\Detail;

class Status
{
    /*
     * Enum values used for activation form status
     */
    const INSTANTLY_ACTIVATED = 'instantly_activated';
    const UNDER_REVIEW        = 'under_review';
    const NEEDS_CLARIFICATION = 'needs_clarification';
    const ACTIVATED           = 'activated';
    const REJECTED            = 'rejected';
    const ACTIVATED_MCC_PENDING = 'activated_mcc_pending';
    const ACTIVATED_KYC_PENDING = 'activated_kyc_pending';

    /*
     * Allowed next activation statuses mapping
     */
    const ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING = [
        self::INSTANTLY_ACTIVATED => [self::UNDER_REVIEW, self::ACTIVATED, self::ACTIVATED_MCC_PENDING],
        self::UNDER_REVIEW        => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED, self::ACTIVATED_MCC_PENDING],
        self::NEEDS_CLARIFICATION => [self::UNDER_REVIEW],
        self::REJECTED            => [self::UNDER_REVIEW],
        self::ACTIVATED_MCC_PENDING => [self::NEEDS_CLARIFICATION, self::ACTIVATED],
        self::ACTIVATED_KYC_PENDING => [self::NEEDS_CLARIFICATION, self::UNDER_REVIEW],
        self::ACTIVATED           => [],
    ];

    const OPEN_STATUSES = [
        self::INSTANTLY_ACTIVATED,
        self::UNDER_REVIEW,
        self::NEEDS_CLARIFICATION,
        self::ACTIVATED_MCC_PENDING,
        self::ACTIVATED_KYC_PENDING
    ];

    const END_STATUSES  = [
        self::ACTIVATED,
        self::REJECTED
    ];
}
