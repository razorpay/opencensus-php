<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class Factory
{
    public static function getIdentityVerificationInstance(string $verificationType)
    {
        switch ($verificationType)
        {
            case Constant::AADHAAR_EKYC:
                return new AadhaarVerificationService();

            default:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY, null, null, 'invalid verification type - '. $verificationType);
        }
    }
}
