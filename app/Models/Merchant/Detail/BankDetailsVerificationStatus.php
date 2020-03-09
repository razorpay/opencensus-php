<?php

namespace RZP\Models\Merchant\Detail;

class BankDetailsVerificationStatus
{
    const INITIATED = 'initiated';

    const VERIFIED = 'verified';

    const FAILED = 'failed';

    const BANK_DETAIL_VERIFICATION_THRESHOLD_FOR_PAN = 51.0;

    /**
     * Allowed next bank detail verification statuses mapping
     */
    const ALLOWED_NEXT_BANK_DETAIL_VERIFICATION_STATUSES_MAPPING = [
        self::FAILED    => [self::VERIFIED],
        self::INITIATED => [self::FAILED, self::VERIFIED],
    ];
}
