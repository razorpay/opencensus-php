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
                    'next' => [
                        'submit_otp',
                        'resend_otp',
                    ]
                ]
            ]
        ];
    }

    public function otpResend(array $input): array
    {
        return [
            'success' => true,
            'data' => [
                'action'     => 'page_resolved',
                'data'       => [
                    'type' => 'otp',
                    'bank' => 'ICIC',
                    'next' => [
                        'submit_otp',
                        'resend_otp',
                    ]
                ],
                'payment_id' => $input['payment_id'],
            ]
        ];
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
