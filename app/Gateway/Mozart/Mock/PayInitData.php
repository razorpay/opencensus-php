<?php

namespace RZP\Gateway\Mozart\Mock;

use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Base\AESCrypto;


class PayInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'Errordescription' => 'SUCCESS (OTP First Process Completed Succesfully)',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'MobileNo' => '2376',
                    'RequestID' => 'RZP190219162906767',
                    'Responsecode' => '0',
                    'status' => 'OTP_sent',
                    '_raw' => '',
                ],
            'next' => [
                'redirect' => [
                    'content' => [
                        'type' => 'otp',
                        'bank' => '',
                        'next' => [
                            'submit_otp',
                        ]
                    ],
                    'method' => 'post',
                    'url' => 'www.test.com',
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_netbanking_payment',
            ['bank' => 'sib']);


        $response = [
            'data' => [],
            'next' => [
                'redirect' => [
                    'content' => [
                        'QS' => 'hBLDFfLDIA91WLou0nWL2BEH3fRBjwk0Vo/yi9soOtmEEsMV8sMgD3VYui7SdYvY4k6AnwcCQqgx+0FxvJLK5YW/AAMuPdhJV0/UDXQ/Xz8O/flBUz9SuiG39um6aA6t2BfJPTuLuDwe+6NyGTqJiW32pkLlLqGdlAoW3VNMiAoJ1B9VOjBTWnheSswTyCB1zg07KIZ2yFAxk/BKm0MmHx3i9O3+AWETmblbvmkZuMj0w7VUBpqHvd1cuLby9thB61WSaaH8t64OczIrmRjcMA=='

                    ],
                    'method' => 'post',
                    'url' => $url,
                ]
            ],
            'error' => null,
            'success' => true,
            'mozart_id' => 'DUMMY_MOZART_ID',
            "external_trace_id" => "DUMMY_REQUEST_ID",
        ];

        return $response;
    }

    public function wallet_phonepe($entities)
    {
        $paymentId = $entities['payment']['id'];

        $publicId = $this->getSignedPaymentId($paymentId);

        $url = $this->route->getPublicCallbackUrlWithHash($publicId);

        $output = [
            'code'    => 'PAYMENT_SUCCESS',
            'merchantId' => 'abc',
            'transactionId' => $entities['payment']['id'],
            'amount' => $entities['payment']['amount'],
            'providerReferenceId' => 'phonepeProviderRefId',
            'key' => $entities['terminal']['gateway_secure_secret'],
        ];

        $salt = $entities['terminal']['gateway_access_code'];

        $output['checksum'] = $this->getGatewayInstance()->generatePhonepeHash($output).'###'.$salt;

        $url .= '?' . http_build_query($output);

        $response = [
            'data' => [
                '_raw' => "",
                'code'=> "PAYMENT_SUCCESS",
                'message'=> "this is successfull",
                'received'=> true,
                'status'=> "authorization_successfull",
                'success'=> true
            ],
            'error'=> null,
            'external_trace_id'=> "",
            'mozart_id'=> "",
            'next'=> [
                'redirect' => [
                    'content' => $output,
                    'method' => 'post',
                    'url' => $url,
                ]
            ],
            'success'=> true
        ];

        return $response;
    }

    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => '000',
                    'messageText' => 'Success',
                    'rrn' => '987654321',
                    'hdnOrderID' => $entities['payment']['id'],
                    'hash' => 'abcd',
                    '_raw' => "{\"rrn\":\"910501000855\",\"txnStatus\":\"PENDING\",\"hdnOrderID\":\"ablxasabsjahskajkg\",\"hash\":\"abcd\",\"messageText\":\"Success\",\"code\":\"0\",\"errorCode\":\"000\",\"txnId\":\"AIR461D026C5D8A48C8AED25897B9AB1877\"}",
                    'status' => 'authorization_successful',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }
}
