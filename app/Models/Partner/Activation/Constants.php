<?php

namespace RZP\Models\Partner\Activation;


use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

class Constants
{
    /*
     * Enum values used for activation form status
     */
    const UNDER_REVIEW        = 'under_review';
    const NEEDS_CLARIFICATION = 'needs_clarification';
    const ACTIVATED           = 'activated';
    const REJECTED            = 'rejected';

    //verification constants
    const PENDING  = 'pending';
    const DISABLED = 'disabled';
    const CAN_SUBMIT          = 'can_submit';
    const STATUS              = 'status';
    const VERIFICATION        = 'verification';
    const ACTIVATION_PROGRESS = 'activation_progress';
    const REQUIRED_FIELDS     = 'required_fields';
    const DISABLE_REASON      = 'disable_reason';
    const DOCUMENTS           = 'documents';

    const ACTIVATION_STATUSES = [self::UNDER_REVIEW, self::ACTIVATED, self::REJECTED, self::REJECTED];

    const ACTION = 'action';

    const PARTNER_MUTEX_LOCK_TIMEOUT = '60';
    const PARTNER_MUTEX_RETRY_COUNT  = '2';

    const TRIGGER_WORKFLOW = 'trigger_workflow';
    const PARTNER_KYC_FLOW = 'partner_kyc_flow';

    /*
     * Allowed next activation statuses mapping
     */
    const NEXT_ACTIVATION_STATUSES_MAPPING = [
        self::UNDER_REVIEW        => [self::NEEDS_CLARIFICATION, self::ACTIVATED, self::REJECTED],
        self::NEEDS_CLARIFICATION => [self::UNDER_REVIEW],
        self::REJECTED            => [self::UNDER_REVIEW],
        self::ACTIVATED           => [],
    ];

    const COMMON_ACTIVATION_FIELDS_MERCHANT_DETAILS = [
        Entity::ACTIVATION_STATUS => Detail\Entity::ACTIVATION_STATUS,
        Entity::LOCKED            => Detail\Entity::LOCKED,
        Entity::SUBMITTED         => Detail\Entity::SUBMITTED
    ];

    const COMMON_ACTIVATION_FIELDS_MERCHANT = [
        Entity::HOLD_FUNDS        => Merchant\Entity::HOLD_FUNDS,
    ];

    const ACTIVATION_ROUTE_NAME     = 'partner_activation_status';
    const PARTNER_CONTROLLER     = 'RZP\Http\Controllers\PartnerController@updatePartnerActivationStatus';
}
