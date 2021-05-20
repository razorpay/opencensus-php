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

    public function verifyOtp(array $input, bool $mock = false): array
    {
        if (in_array($input['otp'], self::MOCK_VALID_OTPS) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
        }

        return ['success' => true];
    }

    public function generateOtp(array $input, $mockInTestMode = true): array
    {
        return [
            self::OTP        => self::MOCK_VALID_OTPS[0],
            self::EXPIRES_AT => Carbon::now()->addMinutes(30)->timestamp,
        ];
    }
}
