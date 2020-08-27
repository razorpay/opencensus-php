<?php

namespace RZP\Models\Merchant\Document;

class OcrVerificationStatus
{
    /**
     * When ocr is successfully verified
     */
    const VERIFIED    = 'verified';

    /**
     * When ocr verification fails because of timeout and external api outages
     */
    const FAILED      = 'failed';

    /**
     * When details not matching with the ocr results
     */
    const NOT_MATCHED = 'not_matched';

    /**
     * When external service input detail is not correct
     */
    const INCORRECT_DETAILS = 'incorrect_details';

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
