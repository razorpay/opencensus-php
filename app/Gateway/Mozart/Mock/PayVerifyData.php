<?php

namespace RZP\Gateway\Mozart\Mock;
use RZP\Gateway\Base;
use RZP\Gateway\Mozart;

class PayVerifyData extends Base\Mock\Server
{
    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'rrn' => '09321',
                    'txnStatus' => 'SUCCESS',
                    'paymentId' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'hash' => 'abcd',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'callback');

        return $response;
    }

    public function bajajfinserv($entities)
    {
        $otp = $entities['gateway']['redirect']['otp'];

        switch($otp)
        {
            case 111111:
                $response = [
                    'data' =>
                        [
                            'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'Key' => $entities['terminal']['gateway_secure_secret'],
                            'MobileNo' => '2376',
                            'RequestID' => 'RZP190219162906768',
                            'Responsecode' => '0',
                            'status' => 'created',
                            'OrderNo' => '104',
                            'DealID' => 'CS905114097404',
                            '_raw' => '',
                        ],
                    'error'             => null,
                    'success'           => true,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
                break;

            default:
                $response = [
                    'data' =>
                        [
                            'Errordescription' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                            'Key' => $entities['terminal']['gateway_secure_secret'],
                            'MobileNo' => '2376',
                            'RequestID' => 'RZP190219162906768',
                            'Responsecode' => 'L3',
                            'status' => 'creation_failed',
                            'OrderNo' => '104',
                            'DealID' => '905909104900',
                            '_raw' => '',
                        ],
                    'error'             => [
                        'description' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                        'gateway_error_code' => 'L3',
                        'gateway_error_description' => 'Transaction Status : Failed [L3].  Reason : INVALID OR EXPIRED OTP',
                        'gateway_status_code' => 200,
                        'internal_error_code' => 'BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED',
                    ],
                    'success'           => false,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
        }

        return $response;
    }

}
