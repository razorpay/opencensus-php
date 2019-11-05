<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;

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
                    'amount' => $entities['payment']['amount'] / 100,
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

    public function upi_citi($entities)
    {
        $data = $entities['gateway']['redirect']['PushNotificationToSSG'];

        $errors = [
            'ZA' => [
                'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                'gateway_error_code'        => 'ZA',
                'gateway_error_description' => 'TRANSACTION DECLINED BY CUSTOMER',
            ],
        ];

        $response = [
            'data' =>
                [
                    'NPCITxnId' => $data['NPCITxnId'] ?? null,
                    'paymentId' => $entities['payment']['id'],
                    'amount'    => intval(floatval($data['SettlementAmount']) * 100),
                    '_raw' => '',
                ],
            'error'             => $errors[$data['RespCode']] ?? null,
            'success'           => $data['RespCode'] === '00',
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'callback');

        return $response;
    }

    public function netbanking_yesb($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                    '_raw'            => 'dummy_raw_value',
                    'paymentId'       => $entities['gateway']['redirect']['paymentId'],
                    'bank_payment_id' => '999999',
                    'amount'          => $entities['gateway']['redirect']['amount'],
                    'status'          => 'callback_successful',
                ],
            ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
            'error' => null,
            'data' => [
                'paymentId' => $entities['payment']['id'],
                'amount' => $entities['payment']['amount'] / 100,
                'bank_payment_id' => '999999',
                'status' => 'callback_successful',
                '_raw' => null
                ],
            ];

        return $response;
    }

    public function netbanking_ubi($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [],
            'success'           => true,
            'error'             => null,
            'data' => [
                'paymentId'         => $entities['payment']['id'],
                'amount'            => $entities['payment']['amount'] / 100,
                'bank_payment_id'   => '999999',
                'payment_status'    => 'Y',
                'status'            => 'callback_successful',
                '_raw'              => null
            ],
        ];
        return $response;
    }

    public function netbanking_scb($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [],
            'success'           => true,
            'error'             => null,
            'data' => [
                'paymentId'         => $entities['payment']['id'],
                'amount'            => $entities['payment']['amount'],
                'bank_payment_id'   => '999999',
                'payment_status'    => 'Y',
                'status'            => 'callback_successful',
                '_raw'              => null
            ],
        ];
        return $response;
    }

    public function netbanking_cbi($entities)
    {
        $response = [
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
            'error' => null,
            'data' => [
                'paymentId' => $entities['payment']['id'],
                'amount' => $entities['payment']['amount'] / 100,
                'bank_payment_id' => '999999',
                'status' => 'callback_successful',
                '_raw' => []
            ],
        ];

        return $response;
    }

    public function netbanking_cub($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['payment']['id'],
                'bank_payment_id' => '999999',
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }

    public function netbanking_ibk($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['payment']['id'],
                'bank_payment_id' => '999999',
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

        return $response;
    }
    
    public function netbanking_idbi($entities)
    {
        $response = [
            'next'              => [],
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'paymentId'       => $entities['payment']['id'],
                'bank_payment_id' => '999999',
                'amount'          => $entities['payment']['amount'] / 100,
                'status'          => 'callback_successful',
            ],
        ];

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

    public function wallet_phonepe($entities)
    {
        if (isset($entities['gateway']['redirect']['data']) == true)
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'amount' => intval($entities['gateway']['redirect']['data']['amount']),
                    'code' => $entities['gateway']['redirect']['code'],
                    'merchantId' => $entities['gateway']['redirect']['data']['merchantId'],
                    'paymentId' => $entities['gateway']['redirect']['data']['transactionId'],
                    'providerReferenceId' => $entities['gateway']['redirect']['data']['providerReferenceId'],
                    'status' => 'callback_successfull'
                ],
                'error' => null,
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => $entities['gateway']['redirect']['success']
            ];

            return $response;
        }

        try
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'amount' => intval($entities['gateway']['redirect']['amount']),
                    'checksum' => $entities['gateway']['redirect']['checksum'],
                    'code' => $entities['gateway']['redirect']['code'],
                    'merchantId' => $entities['gateway']['redirect']['merchantId'],
                    'paymentId' => $entities['gateway']['redirect']['paymentId'],
                    'providerReferenceId' => $entities['gateway']['redirect']['providerReferenceId'],
                    'status' => 'callback_successfull'
                ],
                'error' => null,
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => true
            ];
        }
        catch (\Exception $e)
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'status' => 'callback_failed'
                ],
                'error' => [
                    'description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_error_code' => '',
                    'gateway_error_description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_status_code' => 0,
                    'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
                ],
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => false
            ];
        }

        return $response;
    }

    public function wallet_paypal($entities)
    {
         try
         {
             $response = [
                 'data' => [
                     'amount'    => $entities['payment']['amount'],
                     'paymentId' => $entities['payment']['id'],
                     'PayId'     => $entities['gateway']['redirect']['PayId'],
                     'status'    => $entities['gateway']['redirect']['status'],
                     'token'     => $entities['gateway']['redirect']['token'],
                 ],
                 'error' => null,
                 'external_trace_id' => '',
                 'mozart_id' => '',
                 'next' => [],
                 'success' => true
             ];
         }
        catch (\Exception $e)
        {
            $response = [
                'data' => [
                    '_raw' => '',
                    'status' => 'callback_failed'
                ],
                'error' => [
                    'description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_error_code' => '',
                    'gateway_error_description' => 'INPUT_VALIDATION_FAILED',
                    'gateway_status_code' => 0,
                    'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
                ],
                'external_trace_id' => '',
                'mozart_id' => '',
                'next' => [],
                'success' => false
            ];
        }

        return $response;
    }
}
