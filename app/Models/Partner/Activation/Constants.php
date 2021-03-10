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

    const ACTIVATION_STATUSES = [self::UNDER_REVIEW, self::ACTIVATED, self::REJECTED, self::REJECTED];

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
        Entity::SUBMITTED_AT      => Detail\Entity::SUBMITTED_AT,
        Entity::LOCKED            => Detail\Entity::LOCKED,
        Entity::SUBMITTED         => Detail\Entity::SUBMITTED
    ];

    const COMMON_ACTIVATION_FIELDS_MERCHANT = [
        Entity::HOLD_FUNDS        => Merchant\Entity::HOLD_FUNDS,
    ];
}
