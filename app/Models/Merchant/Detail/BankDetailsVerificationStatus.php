<?php

namespace RZP\Models\Merchant\Detail;

class BankDetailsVerificationStatus
{
    const INITIATED = 'initiated';

    const VERIFIED = 'verified';

    const FAILED = 'failed';

    const NOT_MATCHED = 'not_matched';

    const INCORRECT_DETAILS = 'incorrect_details';

    const BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN = 86.0;

    /**
     * Allowed next bank detail verification statuses mapping
     */
    const ALLOWED_NEXT_BANK_DETAIL_VERIFICATION_STATUSES_MAPPING = [
        self::FAILED    => [self::VERIFIED],
        self::INITIATED => [self::FAILED, self::VERIFIED],
    ];
}
