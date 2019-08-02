<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart;

class RefundData extends Base\Mock\Server
{
    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => '000',
                    'message' => 'successful',
                    'rrn' => '987654321',
                    'txnStatus' => 'SUCCESS',
                    'hdnOrderID' => $entities['refund']['id'],
                    'amount' => $entities['refund']['amount'],
                    'hash' => 'abcd',
                    '_raw' => "{\"rrn\":\"910501000856\",\"txnStatus\":\"SUCCESS\",\"hdnOrderID\":\"ablxasaasbajahskajkg\",\"hash\":\"6256e8a43ba4e56eac1ef8c1faaad0c7236595e3638d74dd7c30e787dc00235624a5d2920230cf5478c88d616474abd1185c236b3c30107f7c931fb7070e20d9\",\"messageText\":\"\",\"code\":\"0\",\"errorCode\":\"000\"}",
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        $this->content($response, 'refund');

        return $response;
    }

    public static function wallet_phonepe($entities)
    {
        $response = [
            'data' => [
                '_raw' => '',
                'code' => 'PAYMENT_SUCCESS',
                'data' => [
                    'amount'                => $entities['refund']['amount'],
                    'merchantId'            => 'abc',
                    'mobileNumber'          => null,
                    'payResponseCode'       => 'PAYMENT_SUCCESS',
                    'providerReferenceId'   => 'phonepeProviderRefId',
                    'status'                => 'SUCCESS',
                    'transactionId'         => $entities['refund']['id'],
                ],
                'message' => 'Payment succeded',
                'received' => true,
                'status' => 'refund_successfull',
                'success' => false
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    public static function wallet_paypal($entities)
    {
        $response = [
            'data' => [
                "_raw"=> "{\"body\":\"{\\\"id\\\":\\\"78027727TF804050W\\\",\\\"status\\\":\\\"COMPLETED\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/refunds/78027727TF804050W\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/8DS61651XA862144J\\\",\\\"rel\\\":\\\"up\\\",\\\"method\\\":\\\"GET\\\"}]}\",\"header\":{\"Date\":[\"Tue, 30 Jul 2019 12:21:15 GMT\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D993411165%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Tue, 30 Jul 2019 12:51:17 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"],\"Vary\":[\"Authorization\"],\"Content-Length\":[\"272\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"e6ed55aa186fc\",\"e6ed55aa186fc\"],\"Content-Type\":[\"application/json\"]},\"status\":201}",
                'id' => '09188073PT4749456',
                'links' => [
                    [
                        "href" => "https://api.sandbox.paypal.com/v2/payments/refunds/09188073PT4749456",
                        "method" => "GET",
                        "rel" => "self",
                    ],
                    [
                        "href" => "https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932",
                        "method" => "GET",
                        "rel" => "up",
                    ],
                ],
                'status' => 'refund_successfull',
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [],
            'success' => true,
        ];

        return $response;
    }

    public static function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'Errordescription' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'RequestID' => 'RZP190219162906769',
                    'Responsecode' => '0',
                    'status' => 'refunded',
                    'received' => 'true',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}

