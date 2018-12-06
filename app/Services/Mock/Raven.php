<?php

namespace RZP\Services\Mock;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Services\Raven as BaseRaven;
use RZP\Exception\BadRequestException;

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
        if ($input[self::OTP] !== self::MOCK_VALID_OTP)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
        }

        return ['success' => true];
    }

    public function generateOtp(array $input): array
    {
        return [
            self::OTP        => self::MOCK_VALID_OTP,
            self::EXPIRES_AT => Carbon::now()->addMinutes(30)->timestamp,
        ];
    }
}
