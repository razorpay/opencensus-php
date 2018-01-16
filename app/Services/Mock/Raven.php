<?php

namespace RZP\Services\Mock;

use RZP\Services\Raven as BaseRaven;

class Raven extends BaseRaven
{
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
        return ['success' => true];
    }
}
