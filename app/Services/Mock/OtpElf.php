<?php

namespace RZP\Services\Mock;

use RZP\Services\OtpElf as BaseOtpElf;

class OtpElf extends BaseOtpElf
{
    public function otpSend(array $input): array
    {
        return [
            'success' => true,
            'data' => [
                'action' => 'page_resolved',
                'data'   => [
                    'type' => 'otp',
                    'bank' => 'ICIC',
                ]
            ]
        ];
    }

    public function otpResend(array $input, bool $mockInTestMode = true): array
    {
        return [self::SMS_ID => self::TEST_SMS_ID];
    }

    public function otpSubmit(array $input): array
    {
        return [
            'success' => true,
            'data' => [
                'action' => 'submit_otp',
                'data'   => [
                    'PaRes' => 'TestPaRes',
                    'MD' => $input['payment_id']
                ]
            ]
        ];
    }
}
