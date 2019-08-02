<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

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
                'bank_payment_id' => 999999,
                'status' => 'callback_successful',
                '_raw' => null
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
                    '_raw' => '{\"body\":\"{\\\"id\\\":\\\"3A830293F71184842\\\",\\\"purchase_units\\\":[{\\\"reference_id\\\":\\\"default\\\",\\\"shipping\\\":{\\\"name\\\":{\\\"full_name\\\":\\\"Usd Rzp\\\"},\\\"address\\\":{\\\"address_line_1\\\":\\\"1 Main St\\\",\\\"admin_area_2\\\":\\\"San Jose\\\",\\\"admin_area_1\\\":\\\"CA\\\",\\\"postal_code\\\":\\\"95131\\\",\\\"country_code\\\":\\\"US\\\"}},\\\"payments\\\":{\\\"captures\\\":[{\\\"id\\\":\\\"6TH801614C6688932\\\",\\\"status\\\":\\\"COMPLETED\\\",\\\"amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"final_capture\\\":true,\\\"disbursement_mode\\\":\\\"INSTANT\\\",\\\"seller_protection\\\":{\\\"status\\\":\\\"ELIGIBLE\\\",\\\"dispute_categories\\\":[\\\"ITEM_NOT_RECEIVED\\\",\\\"UNAUTHORIZED_TRANSACTION\\\"]},\\\"seller_receivable_breakdown\\\":{\\\"gross_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"paypal_fee\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"0.81\\\"},\\\"net_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"9.19\\\"}},\\\"invoice_id\\\":\\\"67g7bb7u6\\\",\\\"custom_id\\\":\\\"67g7bb7u6\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932/refund\\\",\\\"rel\\\":\\\"refund\\\",\\\"method\\\":\\\"POST\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/3A830293F71184842\\\",\\\"rel\\\":\\\"up\\\",\\\"method\\\":\\\"GET\\\"}],\\\"create_time\\\":\\\"2019-08-01T09:23:28Z\\\",\\\"update_time\\\":\\\"2019-08-01T09:23:28Z\\\"}]}}],\\\"payer\\\":{\\\"name\\\":{\\\"given_name\\\":\\\"Usd\\\",\\\"surname\\\":\\\"Rzp\\\"},\\\"email_address\\\":\\\"use@rzp.com\\\",\\\"payer_id\\\":\\\"JHJ57FCJR86LW\\\",\\\"address\\\":{\\\"country_code\\\":\\\"US\\\"}},\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/3A830293F71184842\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"}],\\\"status\\\":\\\"COMPLETED\\\"}\",\"header\":{\"Date\":[\"Thu, 01 Aug 2019 09:23:26 GMT\"],\"Vary\":[\"Authorization\"],\"Content-Type\":[\"application/json\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D2393850461%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Thu, 01 Aug 2019 09:53:29 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"2f5fb42cbc32a\",\"2f5fb42cbc32a\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Content-Length\":[\"1469\"]},\"status\":201}',
                    'amount' => $entities['payment']['amount'],
                    'createdAt' => "2019-08-01T09:25:08Z",
                    "gateway_terminal_id"=> "AR2npSdWeXHqtuW2iGNL2_9q2TGsWl16ZnsTpNNoxrJ2Kv8vjGFPH_HjUVriDDh_-ZxDtA1IKLdJlLf4",
                    'paymentId' => $entities['payment']['id'],
                    'status' => 'callback_successful',
                    "CaptureId" => "8DS61651XA862144J",
                    'token' => $entities['gateway']['redirect']['token'],
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
