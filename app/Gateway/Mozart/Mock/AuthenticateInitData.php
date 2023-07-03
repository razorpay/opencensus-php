<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class AuthenticateInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function hdfc_debit_emi($entities)
    {
        return [
            'data'              =>
                [
                    'status'                     => 'OTP_sent',
                    'BankReferenceNo'            => 'abc123456',
                    'MerchantReferenceNo'        => $entities['payment']['id'],
                    'AuthenticationErrorCode'    => '0000',
                    'AuthenticationErrorMessage' => '',
                    'EligibilityStatus'          => 'Yes',
                    'Token'                      => '123456',
                    '_raw'                       => '',
                ],
            'next'              => [
                'redirect' => [
                    'content' => [
                        'type' => 'otp',
                        'next' => [
                            'submit_otp',
                        ]
                    ],
                    'method'  => 'post',
                    'url'     => $entities['otpSubmitUrl'],
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];
    }

    public function billdesk_sihub($entities)
    {
        return [
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
            'data'              => [
                'amount'    => 6,
                'currency'  => 356,
                'id'        => 'VkHYuA3NH3',
                'status'    => 'pending',
                'frequency' => 'monthly'
            ],
        ];
    }
}
