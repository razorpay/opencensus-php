<?php

namespace RZP\Models\Merchant\Detail;

class Status
{
    /*
     * Enum values used for activation form status
     */
    const INSTANTLY_ACTIVATED       = 'instantly_activated';
    const UNDER_REVIEW              = 'under_review';
    const NEEDS_CLARIFICATION       = 'needs_clarification';
    const ACTIVATED                 = 'activated';
    const REJECTED                  = 'rejected';
    const ACTIVATED_MCC_PENDING     = 'activated_mcc_pending';
    const ACTIVATED_KYC_PENDING     = 'activated_kyc_pending';
    const KYC_QUALIFIED_UNACTIVATED = 'kyc_qualified_unactivated';

    /*
     * Allowed next activation statuses mapping
     */
    const ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING = [
        self::INSTANTLY_ACTIVATED       => [self::UNDER_REVIEW, self::ACTIVATED, self::ACTIVATED_MCC_PENDING],
        self::UNDER_REVIEW              => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED, self::ACTIVATED_MCC_PENDING, self::ACTIVATED_KYC_PENDING],
        self::NEEDS_CLARIFICATION       => [self::UNDER_REVIEW],
        self::REJECTED                  => [self::UNDER_REVIEW],
        self::ACTIVATED_MCC_PENDING     => [self::NEEDS_CLARIFICATION, self::ACTIVATED],
        self::ACTIVATED_KYC_PENDING     => [self::NEEDS_CLARIFICATION, self::UNDER_REVIEW],
        self::ACTIVATED                 => [],
    ];

    /*
     * Allowed next activation statuses mapping with new state (KQU)
     */
    const ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_WITH_KQU = [
        self::INSTANTLY_ACTIVATED       => [self::UNDER_REVIEW, self::ACTIVATED, self::ACTIVATED_MCC_PENDING],
        self::UNDER_REVIEW              => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED, self::ACTIVATED_MCC_PENDING, self::ACTIVATED_KYC_PENDING, self::KYC_QUALIFIED_UNACTIVATED],
        self::NEEDS_CLARIFICATION       => [self::UNDER_REVIEW],
        self::REJECTED                  => [self::UNDER_REVIEW],
        self::ACTIVATED_MCC_PENDING     => [self::NEEDS_CLARIFICATION, self::KYC_QUALIFIED_UNACTIVATED, self::ACTIVATED],
        self::ACTIVATED_KYC_PENDING     => [self::NEEDS_CLARIFICATION, self::UNDER_REVIEW],
        self::KYC_QUALIFIED_UNACTIVATED => [self::ACTIVATED],
        self::ACTIVATED                 => [],
    ];

    /*
     * With new flow for Linked Accounts where activation status can go back to 'under_review'
     * from 'activated' status, created a new activation status mapping specifically for linked accounts
     * Jira EPA-168
    */
    const ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_LINKED_ACCOUNT = [
        self::INSTANTLY_ACTIVATED   => [self::UNDER_REVIEW, self::ACTIVATED, self::ACTIVATED_MCC_PENDING],
        self::UNDER_REVIEW          => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED, self::ACTIVATED_MCC_PENDING, self::ACTIVATED_KYC_PENDING],
        self::NEEDS_CLARIFICATION   => [self::UNDER_REVIEW],
        self::REJECTED              => [self::UNDER_REVIEW],
        self::ACTIVATED_MCC_PENDING => [self::NEEDS_CLARIFICATION, self::ACTIVATED],
        self::ACTIVATED_KYC_PENDING => [self::NEEDS_CLARIFICATION, self::UNDER_REVIEW],
        self::ACTIVATED             => [self::UNDER_REVIEW,],
    ];

    const MERCHANT_OPEN_STATUSES = [
        self::INSTANTLY_ACTIVATED,
        self::UNDER_REVIEW,
        self::NEEDS_CLARIFICATION,
        self::ACTIVATED_MCC_PENDING
    ];
    const MERCHANT_L2_OPEN_STATUSES = [
        self::UNDER_REVIEW,
        self::NEEDS_CLARIFICATION,
        self::ACTIVATED_MCC_PENDING
    ];

    const SUBMERCHANT_OPEN_STATUSES = [
        self::INSTANTLY_ACTIVATED,
        self::UNDER_REVIEW,
        self::NEEDS_CLARIFICATION,
        self::ACTIVATED_MCC_PENDING,
        self::ACTIVATED_KYC_PENDING
    ];

    const MERCHANT_NO_DOC_OPEN_STATUSES = [
        self::UNDER_REVIEW,
        self::NEEDS_CLARIFICATION,
        self::ACTIVATED_KYC_PENDING
    ];

    const END_STATUSES = [
        self::ACTIVATED,
        self::REJECTED
    ];

    const PAYMENTS_ENABLED_STATUSES = [
        self::INSTANTLY_ACTIVATED,
        self::ACTIVATED_MCC_PENDING,
        self::ACTIVATED_KYC_PENDING,
        self::ACTIVATED
    ];
}
