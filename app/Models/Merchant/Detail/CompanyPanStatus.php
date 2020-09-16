<?php

namespace RZP\Models\Merchant\Detail;

class CompanyPanStatus
{
    /**
     * When pan is successfully verified
     */
    const VERIFIED = 'verified';

    /**
     * When pan verify failed because of timeout and external api outages
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

    const VERIFICATION_THRESHOLD = 81;
}
