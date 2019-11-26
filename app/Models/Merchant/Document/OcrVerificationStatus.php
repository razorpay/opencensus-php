<?php

namespace RZP\Models\Merchant\Document;

class OcrVerificationStatus
{
    const VERIFIED = 'verified';

    const FAILED = 'failed';

    const OCR_VERIFICATION_THRESHOLD = 70.0;

    public static function isValid($type): bool
    {
        if ($type === self::OCR_VERIFICATION_THRESHOLD)
        {
            return false;
        }

        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }
}
