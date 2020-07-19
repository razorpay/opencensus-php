<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class AuthInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function wallet_paypal($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'wallet_paypal', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data' => [
                "OrderId" => "5YG0152953511483M",
                "_raw" => "{\"body\":\"{\\\"id\\\":\\\"5YG0152953511483M\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://www.sandbox.paypal.com/checkoutnow?token=5YG0152953511483M\\\",\\\"rel\\\":\\\"approve\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M\\\",\\\"rel\\\":\\\"update\\\",\\\"method\\\":\\\"PATCH\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M/capture\\\",\\\"rel\\\":\\\"capture\\\",\\\"method\\\":\\\"POST\\\"}],\\\"status\\\":\\\"CREATED\\\"}\",\"header\":{\"Date\":[\"Tue, 30 Jul 2019 11:11:44 GMT\"],\"Content-Length\":[\"501\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D4028973149%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Tue, 30 Jul 2019 11:41:45 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"],\"Vary\":[\"Authorization\"],\"Content-Type\":[\"application/json\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"5ab1b731c02a6\",\"5ab1b731c02a6\"]},\"status\":201}",
                "ActionLink" => [
                    [
                        "href" => "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449",
                        "method" =>  "GET",
                        "rel" =>  "self",
                    ],
                    [
                        "href" =>  "https://www.sandbox.paypal.com/checkoutnow?token=8WA29343W72537449",
                        "method" =>  "GET",
                        "rel" =>  "approve",
                    ],
                    [
                        "href" =>  "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449",
                        "method" =>  "PATCH",
                        "rel" =>  "update",
                    ],
                    [
                        "href" =>  "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449/capture",
                        "method" =>  "POST",
                        "rel" =>  "capture",
                    ]
                ],
                'status' => 'authorization_successful',
            ],
            'error'             => null,
            'success'           => true,
            "next" => [
                "redirect" => [
                    "method" => "get",
                    "url" => $url,
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function upi_mindgate($entities)
    {
        $response = [
            'data' =>
                [
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'PENDING',
                    'reqStatus' => 'S',
                    'message' => 'Mandate Request Initiated to NPCI',
                    'payerVPA' => 'testvpa@yesb',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'errorCode' => 'MD200',
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }


    public function upi_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'PENDING',
                    'reqStatus' => 'S',
                    'message' => 'Mandate Request Initiated to NPCI',
                    'payerVPA' => 'testvpa@yesb',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'errorCode' => 'MD200',
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'ResponseCode'          => '000',
                    'MobileNumber'          => '93884739457',
                    'AppName'               => 'MerchantName',
                    'TransactionIdentifier' => '3479278',
                    '_raw'                  => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}
