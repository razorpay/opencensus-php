<?php

namespace RZP\Services\Mock;

use RZP\Services\OtpElf as BaseOtpElf;

class OtpElf extends BaseOtpElf
{
    public function otpSend(array $input): array
    {

        $response = [
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

        if ($input['card']['iin'] === '411111')
        {
            switch ($input['card']['last4'])
            {
                case '1137' : $response = [
                                "success" => false,
                                "error" => [
                                    "reason" => "ROUTE_NOT_VALID_FOR_PAYMENT_ID",
                                    "fatal"  => false,
                                    ]
                                ];
                                break;

                case '1145' : $response = [
                                "success" => false,
                                "error" => [
                                    "reason" => "CARD_BLOCKED",
                                    "fatal"  => false,
                                    ]
                                ];
                                break;

                case '1152' : $response = [
                                "success" => false,
                                "error" => [
                                    "reason" => "BANK_SERVICE_DOWN",
                                    "fatal"  => false,
                                ]
                            ];
                            break;

                case '1160' : $response = [
                                "success" => false,
                                "error" => [
                                    "reason" => "PAGE_TYPE_UNKNOWN",
                                    "fatal"  => false,
                                ]
                            ];
                            break;

            }
        }

        return $response;
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
