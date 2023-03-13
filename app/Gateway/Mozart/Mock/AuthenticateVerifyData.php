<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class AuthenticateVerifyData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function hdfc_debit_emi($entities)
    {
        $otp = $entities['gateway']['redirect']['otp'];

        switch ($otp) {
            case 111111:
                $response = [
                    'data'              =>
                        [
                            'ValidateOtpErrorCode'    => '0000',
                            'ValidateOtpErrorMessage' => '',
                            '_raw'                    => '',
                        ],
                    'error'             => null,
                    'success'           => true,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
                break;
            default:
                $response = [
                    'data'              => [
                        'ValidateOtpErrorCode'    => 'A061',
                        'ValidateOtpErrorMessage' => 'Invalid OTP',
                        '_raw'                    => '',
                    ],
                    'error'             => [
                        'description'               => 'Invalid OTP',
                        'gateway_error_code'        => 'A061',
                        'gateway_error_description' => 'Invalid OTP',
                        'gateway_status_code'       => 200,
                        'internal_error_code'       => 'BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED',
                    ],
                    'success'           => false,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
        }

        return $response;
    }

    public function paysecure($entities)
    {
        return [
            "data" => [
                'si_registration_id' => 'DummyRegistrationID',
                'status'             => 'success',
            ],
        ];
    }

    public function billdesk_sihub($entities)
    {
        return [
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];
    }
}
