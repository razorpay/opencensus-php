<?php

namespace RZP\Gateway\Mozart\Mock;

use phpseclib\Crypt\AES;

use RZP\Gateway\Base;

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

    public function wallet_phonepe($entities)
    {
        $paymentId = $entities['payment']['id'];

        $publicId = $this->getSignedPaymentId($paymentId);

        $this->gateway = $entities['payment']['gateway'];

        $url = $this->route->getUrlWithPublicAuth(
            'mock_wallet_payment_get',
            [
                'wallet' => $entities['payment']['wallet'],
                'paymentId' => $entities['payment']['id']
            ]);

        $output = [
            'code'    => 'PAYMENT_SUCCESS',
            'merchantId' => 'abc',
            'transactionId' => $entities['payment']['id'],
            'amount' => $entities['payment']['amount'],
            'providerReferenceId' => 'phonepeProviderRefId',
        ];

        $response = [
            'data' => [
                '_raw' => '',
                'code'=> '',
                'message'=> '',
                'received'=> true,
                'status'=> 'authorization_successfull',
                'success'=> null
            ],
            'error'=> null,
            'external_trace_id'=> '',
            'mozart_id'=> '',
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
                    '_raw' => '{"rrn":"910501000855","txnStatus":"PENDING","hdnOrderID":"ablxasabsjahskajkg","hash":"abcd","messageText":"Success","code":"0","errorCode":"000","txnId":"AIR461D026C5D8A48C8AED25897B9AB1877"}',
                    'status' => 'authorization_successful',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
             'mock_netbanking_payment',
                       ['bank' => 'sib']);

        $content = [
            'ShoppingMallTranFG.TRAN_CRN' => $entities['payment']['currency'],
            'ShoppingMallTranFG.TXN_AMT'  => $entities['payment']['amount'],
            'ShoppingMallTranFG.PID'      => $entities['terminal']['gateway_merchant_id'],
            'ShoppingMallTranFG.PRN'      => $entities['payment']['id'],
            'ShoppingMallTranFG.ITC'      => 'Razorpay',
            'ShoppingMallTranFG.RU'       => $entities['callbackUrl']
        ];

        $aes = new Base\AESCrypto(
                             AES::MODE_ECB,
                             $this->app['config']->get('gateway.mozart.netbanking_sib_test_hash_secret')
                           );

        $response = [
            'data' => [],
            'error'             => null,
            'success'           => true,
            'next' => [
                "redirect" => [
                    "content" => [
                        'QS' => base64_encode($aes->encryptString(http_build_query($content))),
                    ],
                    'method' => 'post',
                    'url' => $url
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }
}