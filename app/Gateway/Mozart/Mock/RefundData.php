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

    public static function cred($entities)
    {
        return;
    }

    public static function wallet_phonepe($entities)
    {
        $response = [
            'data' => [
                '_raw' => '',
                'code' => 'PAYMENT_SUCCESS',
                'amount'                => $entities['refund']['amount'],
                'merchantId'            => 'abc',
                'mobileNumber'          => null,
                'payResponseCode'       => 'PAYMENT_SUCCESS',
                'providerReferenceId'   => 'phonepeProviderRefId',
                'data_status'           => 'SUCCESS',
                'transactionId'         => $entities['refund']['id'],
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

    public static function hdfc_debit_emi($entities)
    {
        $response = [
            'data' =>
                [
                    'ErrorCode'               => '0000',
                    'OrderCancellationStatus' => 'Yes',
                    'BankReferenceNo'         => '12344',
                    '_raw'                    => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public static function getsimpl($entities)
    {
        $response = [
            'data' => [
                'api_version' => '4.0',
                '_raw'        => '{\"data\":{\"transaction_id\":\"16e94d67-7e97-4744-b90c-8a9f534e744f\",\"refunded_transaction_id\":\"f2badf4d-528b-4c90-aad9-05c7ab716307\"},\"Http_status\":200,\"success\":true}',
                'data' => [
                    'refunded_transaction_id' => 'f2badf4d-528b-4c90-aad9-05c7ab716307',
                    'transaction_id'          => $entities['gateway']['pay_init']['data']['transaction']['id']
                ],
                'status'  => 'refund_successful',
                'success' => true
            ],
            'error'             => null,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'next'              => [],
            'success'           => true
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

    public static function netbanking_scb($entities)
    {
        $response = [
            'data' =>
                [
                    "_raw" => "{\"data\":{\"refund_id\":5621,\"transaction_id\":\"HDVISC1234\",\"merchant_order_id\":\"1234\",\"merchant_refund_id\": \"123456\",\"refund_reference_no\":\"RRN1234\"}}",
        "merchant_order_id" => "1234",
        "success_status" => "success",
        "refund_id" => 5621,
        "merchant_refund_id" => "123456",
        "refund_reference_no" => "RRN1234",
        "transaction_id" => "HDVISC1234",
        "status" => "refund_successful"
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function upi_juspay($entities)
    {
        $response = [
            'data' => [
                    "_raw" => '{\"status\":\"SUCCESS\",\"responseCode\":\"SUCCESS\",\"responseMessage\":\"SUCCESS\",\"payload\":{\"merchantId\":\"MERCHANT\",\"merchantChannelId\":\"MERCHANTAPP\",\"merchantRequestId\":\"heyyourefund4\",\"transactionAmount\":\"20.00\",\"refundAmount\":\"19.00\",\"gatewayTransactionId\":\"Some transaction id\",\"gatewayResponseCode\":\"00\",\"gatewayResponseMessage\":\"Refund accepted successfully\"},\"udfParameters\":\"{}\"}',
                    "gatewayResponseCode"       => "00",
                    "gatewayResponseMessage"    => "Refund accepted successfully",
                    "gatewayTransactionId"      => "Some transaction id",
                    "merchantChannelId"         => "MERCHANTAPP",
                    "merchantId"                => "MERCHANT",
                    "merchantRequestId"         => "heyyourefund4",
                    "refundAmount"              => "19.00",
                    "responseCode"              => "SUCCESS",
                    "responseMessage"           => "SUCCESS",
                    "status"                    => "refund_initiated_successfully",
                    "apiStatus"                 => "SUCCESS",
                    "transactionAmount"         => $entities['payment']['amount']
                ],
                'error'             => null,
                'success'           => true,
                'mozart_id'         => '',
                'external_trace_id' => '',
        ];

        switch ($entities['payment']['description']){
            case 'failedRefund':
                $response['success'] = false;
                $response['data']['_raw'] = '{\"status\":\"SUCCESS\",\"responseCode\":\"SUCCESS\",\"responseMessage\":\"SUCCESS\",\"payload\":{\"merchantId\":\"MERCHANT\",\"merchantChannelId\":\"MERCHANTAPP\",\"merchantRequestId\":\"heyyourefund4\",\"transactionAmount\":\"20.00\",\"refundAmount\":\"19.00\",\"gatewayTransactionId\":\"Some transaction id\",\"gatewayResponseCode\":\"Else\",\"gatewayResponseMessage\":\"Refund initiation failed\"},\"udfParameters\":\"{}\"}';
                $response['error']   = [
                    "description"                  => "Transaction Failed",
                    "gateway_error_code"           => "Else",
                    "gateway_error_description"    => "Transaction Failed",
                    "gateway_status_code"          => 200,
                    "internal_error_code"          => "GATEWAY_ERROR_TRANSACTION_FAILED"
                ];
                break;
        }

        return $response;
    }
}

