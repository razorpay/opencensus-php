<?php

namespace RZP\Models\ClarificationDetail;



class Constants
{

    const  SUBMITTED           = 'submitted';
    const  UNDER_REVIEW        = 'under_review';
    const  VERIFIED            = 'verified';
    const  REJECTED            = 'rejected';
    const  NEEDS_CLARIFICATION = 'needs_clarification';

    const CLARIFICATION_DETAILS_STATUS = [
        self::SUBMITTED,
        self::UNDER_REVIEW,
        self::VERIFIED,
        self::REJECTED,
        self::NEEDS_CLARIFICATION,
    ];

    const FIELD_DETAILS = 'field_details';
    const SUBMIT        = 'submit';
    const TYPE          = 'type';
    const TEXT          = 'text';

    const PREDEFINED = 'predefined';
    const CUSTOM     = 'custom';
    const NOTE       = 'note';


    const ADMIN_EMAIL               = 'admin_email';
    const NC_COUNT                  = 'nc_count';
    const CLARIFICATION_REASONS     = 'clarification_reasons';
    const OLD_CLARIFICATION_REASONS = 'old_clarification_reasons';
    const MERCHANT                  = 'merchant';
}
