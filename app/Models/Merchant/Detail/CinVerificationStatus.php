<?php

namespace RZP\Models\Merchant\Detail;

class CinVerificationStatus
{

    /**
     * When cin is successfully verified
     */
    const VERIFIED = 'verified';

    /**
     * When cin verify failed because of timeout and external api outages
     */
    const FAILED = 'failed';

    /**
     * When cin service explicitly says details not matching
     */
    const NOT_MATCHED = 'not_matched';

    /**
     * When external service input detail is not correct
     */
    const INCORRECT_DETAILS = 'incorrect_details';

    const CIN_VERIFICATION_THRESHOLD = 70.0;
}
