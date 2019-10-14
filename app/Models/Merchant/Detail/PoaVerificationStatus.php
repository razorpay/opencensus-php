<?php

namespace RZP\Models\Merchant\Detail;

class PoaVerificationStatus
{

    const VERIFIED = 'verified';

    const FAILED = 'failed';

    public static function isValid($type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    /**
     * Allowed next bank detail verification statuses mapping
     */
    const ALLOWED_NEXT_POA_VERIFICATION_STATUSES_MAPPING = [
        self::FAILED => [self::VERIFIED],
    ];

}
