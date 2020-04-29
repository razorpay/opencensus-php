<?php

namespace RZP\Models\Merchant\Detail;

class GSTINVerificationStatus
{
    /**
     * When gstin is successfully verified
     */
    const VERIFIED = 'verified';

    /**
     * When gstin verify failed because of timeout and external api outages
     */
    const FAILED = 'failed';

    /**
     * When external service explicitly says details not matching
     */
    const NOT_MATCHED = 'not_matched';

    /**
     * When external service input detail is not correct
     */
    const INCORRECT_DETAILS = 'incorrect_details';

    const GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD     = 70.0;
    const GSTIN_VERIFICATION_PROMOTER_PAN_NAME_THRESHOLD = 70.0;
}
