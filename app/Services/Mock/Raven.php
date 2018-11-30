<?php

namespace RZP\Services\Mock;

use RZP\Services\Raven as BaseRaven;

class Raven extends BaseRaven
{
    // If raven service is mock, this OTP only is evaluated as true in verify.
    const MOCK_VALID_OTP = '0007';

    public function sendOtp(array $input): array
    {
        return [self::SMS_ID => self::TEST_SMS_ID];
    }

    public function sendSms(array $input, bool $mockInTestMode = true): array
    {
        return [self::SMS_ID => self::TEST_SMS_ID];
    }

    public function verifyOtp(array $input): array
    {
        return ['success' => ($input['otp'] === self::MOCK_VALID_OTP)];
    }
}
